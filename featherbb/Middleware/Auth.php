<?php
/**
 *
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 *
 * $app = new \Slim\Slim();
 * $app->add(new \Slim\Extras\Middleware\FeatherBBAuth());
 *
 */

namespace FeatherBB\Middleware;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Error;
use FeatherBB\Core\Random;
use FeatherBB\Core\Track;
use FeatherBB\Core\Utils;
use FeatherBB\Model\Cache;
use FeatherBB\Model\Auth as AuthModel;
use Firebase\JWT\JWT;

class Auth
{
    protected function getCookieData($authCookie = null)
    {
        if ($authCookie) {
            /*
             * Extract the jwt from the Bearer
             */
            list($jwt) = sscanf($authCookie, 'Bearer %s');

            if ($jwt) {
                try {
                    /*
                    * decode the jwt using the key from config
                    */
                    $secretKey = base64_decode(ForumSettings::get('jwt_token'));
                    $token = JWT::decode($jwt, $secretKey, [ForumSettings::get('jwt_algorithm')]);

                    return $token;
                } catch (\Firebase\JWT\ExpiredException $e) {
                    // TODO: (Optionnal) add flash message to say token has expired
                    return false;
                } catch (\Firebase\JWT\SignatureInvalidException $e) {
                    // If token secret has changed (config.php file removed then regenerated)
                    return false;
                }
            } else {
                // Token is not present (or invalid) in cookie
                return false;
            }
        } else {
            // Auth cookie is not present in headers
            return false;
        }
    }

    public function updateOnline()
    {
        // Define this if you want this visit to affect the online list and the users last visit data
        if (!defined('FEATHER_QUIET_VISIT')) {
            // Update the online list
            if (!User::get()->logged) {
                User::get()->logged = Container::get('now');

                // With MySQL/MySQLi/SQLite, REPLACE INTO avoids a user having two rows in the online table
                switch (ForumSettings::get('db_type')) {
                    case 'mysql':
                    case 'mysqli':
                    case 'mysql_innodb':
                    case 'mysqli_innodb':
                    case 'sqlite':
                    case 'sqlite3':
                        DB::table('online')->rawExecute('REPLACE INTO '.ForumSettings::get('db_prefix').'online (user_id, ident, logged) VALUES(:user_id, :ident, :logged)', [':user_id' => User::get()->id, ':ident' => User::get()->username, ':logged' => User::get()->logged]);
                        break;

                    default:
                        DB::table('online')->rawExecute('INSERT INTO '.ForumSettings::get('db_prefix').'online (user_id, ident, logged) SELECT :user_id, :ident, :logged WHERE NOT EXISTS (SELECT 1 FROM '.ForumSettings::get('db_prefix').'online WHERE user_id=:user_id)', [':user_id' => User::get()->id, ':ident' => User::get()->username, ':logged' => User::get()->logged]);
                        break;
                }

                // Reset tracked topics
                Track::setTrackedTopics(null);
            } else {
                // Special case: We've timed out, but no other user has browsed the forums since we timed out
                if (User::get()->logged < (Container::get('now')-ForumSettings::get('o_timeout_visit'))) {
                    DB::table('users')->where('id', User::get()->id)
                        ->findOne()
                        ->set('last_visit', User::get()->logged)
                        ->save();
                    User::get()->last_visit = User::get()->logged;
                }

                $idle_sql = (User::get()->idle == '1') ? ', idle=0' : '';

                DB::table('online')->rawExecute('UPDATE '.ForumSettings::get('db_prefix').'online SET logged='.Container::get('now').$idle_sql.' WHERE user_id=:user_id', [':user_id' => User::get()->id]);

                // Update tracked topics with the current expire time
                $cookie_tracked_topics = Container::get('cookie')->get(ForumSettings::get('cookie_name').'_track');
                if (isset($cookie_tracked_topics)) {
                    Track::setTrackedTopics(json_decode($cookie_tracked_topics, true));
                }
            }
        } else {
            if (!User::get()->logged) {
                User::get()->logged = User::get()->last_visit;
            }
        }
    }

    public function updateUsersOnline()
    {
        // Fetch all online list entries that are older than "o_timeout_online"
        $select_update_users_online = ['user_id', 'ident', 'logged', 'idle'];

        $result = DB::table('online')
                    ->selectMany($select_update_users_online)
                    ->whereLt('logged', Container::get('now')-ForumSettings::get('o_timeout_online'))
                    ->findMany();

        foreach ($result as $cur_user) {
            // If the entry is a guest, delete it
            if ($cur_user['user_id'] == '1') {
                DB::table('online')->where('ident', $cur_user['ident'])
                    ->deleteMany();
            } else {
                // If the entry is older than "o_timeout_visit", update last_visit for the user in question, then delete him/her from the online list
                if ($cur_user['logged'] < (Container::get('now')-ForumSettings::get('o_timeout_visit'))) {
                    DB::table('users')->where('id', $cur_user['user_id'])
                        ->findOne()
                        ->set('last_visit', $cur_user['logged'])
                        ->save();
                    DB::table('online')->where('user_id', $cur_user['user_id'])
                        ->deleteMany();
                } elseif ($cur_user['idle'] == '0') {
                    DB::table('online')->where('user_id', $cur_user['user_id'])
                        ->updateMany('idle', 1);
                }
            }
        }
    }

    public function checkBans()
    {
        // Admins and moderators aren't affected
        if (User::isAdminMod() || !Container::get('bans')) {
            return;
        }

        // Add a dot or a colon (depending on IPv4/IPv6) at the end of the IP address to prevent banned address
        // 192.168.0.5 from matching e.g. 192.168.0.50
        $user_ip = Utils::getIp();
        $user_ip .= (strpos($user_ip, '.') !== false) ? '.' : ':';

        $bans_altered = false;
        $is_banned = false;

        foreach (Container::get('bans') as $cur_ban) {
            // Has this ban expired?
            if ($cur_ban['expire'] != '' && $cur_ban['expire'] <= time()) {
                DB::table('bans')->where('id', $cur_ban['id'])
                    ->deleteMany();
                $bans_altered = true;
                continue;
            }

            if ($cur_ban['username'] != '' && \utf8\to_lower(User::get()->username) == \utf8\to_lower($cur_ban['username'])) {
                $is_banned = true;
            }

            if ($cur_ban['ip'] != '') {
                $cur_ban_ips = explode(' ', $cur_ban['ip']);

                $num_ips = count($cur_ban_ips);
                for ($i = 0; $i < $num_ips; ++$i) {
                    // Add the proper ending to the ban
                    if (strpos($user_ip, '.') !== false) {
                        $cur_ban_ips[$i] = $cur_ban_ips[$i].'.';
                    } else {
                        $cur_ban_ips[$i] = $cur_ban_ips[$i].':';
                    }

                    if (substr($user_ip, 0, strlen($cur_ban_ips[$i])) == $cur_ban_ips[$i]) {
                        $is_banned = true;
                        break;
                    }
                }
            }

            if ($is_banned) {
                DB::table('online')
                    ->where('ident', User::get()->username)
                    ->deleteMany();
                throw new Error(__('Ban message').' '.(($cur_ban['expire'] != '') ? __('Ban message 2').' '.strtolower(Utils::formatTime($cur_ban['expire'], true)).'. ' : '').(($cur_ban['message'] != '') ? __('Ban message 3').'<br /><br /><strong>'.Utils::escape($cur_ban['message']).'</strong><br /><br />' : '<br /><br />').__('Ban message 4').' <a href="mailto:'.Utils::escape(ForumSettings::get('o_admin_email')).'">'.Utils::escape(ForumSettings::get('o_admin_email')).'</a>.', 403, false, true);
            }
        }

        // If we removed any expired bans during our run-through, we need to regenerate the bans cache
        if ($bans_altered) {
            Container::get('cache')->store('bans', Cache::getBans());
        }
    }

    public function maintenanceMessage()
    {
        // Admins and moderators aren't affected
        if (User::isAdminMod()) {
            return;
        }

        // Deal with newlines, tabs and multiple spaces
        $pattern = ["\t", '  ', '  '];
        $replace = ['&#160; &#160; ', '&#160; ', ' &#160;'];
        $message = str_replace($pattern, $replace, ForumSettings::get('o_maintenance_message'));

        if (ForumSettings::get('o_maintenance') == 1) {
            throw new Error($message, 403, false, true);
        }
    }

    private function loadDefaultUser()
    {
        $user = AuthModel::loadUser(1);

        $user->is_guest = true;
        $user->is_admmod = false;

        return $user;
    }

    public function __invoke($req, $res, $next)
    {
        $authCookie = Container::get('cookie')->get(ForumSettings::get('cookie_name'));

        $jwt = false;

        try {
            $jwt = $this->getCookieData($authCookie);
        } catch (\Exception $e) {
            $user = $this->loadDefaultUser();

            // Add $user as guest to DIC
            Container::set('user', $user);
        }

        if ($jwt && $user = AuthModel::loadUser($jwt->data->userId)) {

            // Load permissions and preferences for logged user
            Container::get('perms')->getUserPermissions($user);
            $user->prefs = Container::get('prefs')->loadPrefs($user);

            $expires = ($jwt->exp > Container::get('now') + ForumSettings::get('o_timeout_visit')) ? Container::get('now') + 1209600 : Container::get('now') + ForumSettings::get('o_timeout_visit');

            $user->is_guest = false;

            if (!is_dir(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.$user->prefs['language'])) {
                Container::get('prefs')->setUser($user, ['language' => ForumSettings::get('language')]);
            }
            if (!file_exists(ForumEnv::get('FEATHER_ROOT').'style/themes/'.$user->prefs['style'].'/style.css')) {
                Container::get('prefs')->setUser($user, ['style' => ForumSettings::get('style')]);
            }

            // Add user to DIC
            Container::set('user', $user);

            // Refresh cookie to avoid re-logging between idle
            $jwt = AuthModel::generateJwt($user, $expires);
            AuthModel::setCookie('Bearer '.$jwt, $expires);

            $this->updateOnline();
        } else {
            $user = $this->loadDefaultUser();

            // Update online list
            if (!$user->logged) {
                $user->logged = time();

                // With MySQL/MySQLi/SQLite, REPLACE INTO avoids a user having two rows in the online table
                switch (ForumSettings::get('db_type')) {
                    case 'mysql':
                    case 'mysqli':
                    case 'mysql_innodb':
                    case 'mysqli_innodb':
                    case 'sqlite':
                    case 'sqlite3':
                    DB::table('online')->rawExecute('REPLACE INTO '.ForumSettings::get('db_prefix').'online (user_id, ident, logged) VALUES(1, :ident, :logged)', [':ident' => Utils::getIp(), ':logged' => $user->logged]);
                        break;

                    default:
                        DB::table('online')->rawExecute('INSERT INTO '.ForumSettings::get('db_prefix').'online (user_id, ident, logged) SELECT 1, :ident, :logged WHERE NOT EXISTS (SELECT 1 FROM '.ForumSettings::get('db_prefix').'online WHERE ident=:ident)', [':ident' => Utils::getIp(), ':logged' => $user->logged]);
                        break;
                }
            } else {
                DB::table('online')->where('ident', Utils::getIp())
                     ->updateMany('logged', time());
            }

            // Load permissions and preferences for guest user
            Container::get('perms')->getUserPermissions($user);
            $user->prefs = Container::get('prefs')->loadPrefs($user);

            // Add $user as guest to DIC
            Container::set('user', $user);
        }

        Lang::load('common');
        // Load bans from cache
        if (!Container::get('cache')->isCached('bans')) {
            Container::get('cache')->store('bans', Cache::getBans());
        }

        // Add bans to the container
        Container::set('bans', Container::get('cache')->retrieve('bans'));

        // load theme assets, also set setStyle, also init template engine, also load template config
        Container::get('template')->setStyle($user->prefs['style']);

        // Check if current user is banned
        $this->checkBans();

        // Check if we have to display the maintenance message
        $this->maintenanceMessage();

        // Update online list
        $this->updateUsersOnline();

        return $next($req, $res);
    }
}
