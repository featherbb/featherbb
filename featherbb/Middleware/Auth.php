<?php
/**
 *
 * Copyright (C) 2015-2016 FeatherBB
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
    protected function get_cookie_data($authCookie = null)
    {
        if ($authCookie) {
            /*
             * Extract the jwt from the Bearer
             */
            list($jwt) = sscanf( $authCookie, 'Bearer %s');

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

    public function update_online()
    {
        // Define this if you want this visit to affect the online list and the users last visit data
        if (!defined('FEATHER_QUIET_VISIT')) {
            // Update the online list
            if (!Container::get('user')->logged) {
                Container::get('user')->logged = Container::get('now');

                // With MySQL/MySQLi/SQLite, REPLACE INTO avoids a user having two rows in the online table
                switch (ForumSettings::get('db_type')) {
                    case 'mysql':
                    case 'mysqli':
                    case 'mysql_innodb':
                    case 'mysqli_innodb':
                    case 'sqlite':
                    case 'sqlite3':
                        DB::for_table('online')->raw_execute('REPLACE INTO '.ForumSettings::get('db_prefix').'online (user_id, ident, logged) VALUES(:user_id, :ident, :logged)', array(':user_id' => Container::get('user')->id, ':ident' => Container::get('user')->username, ':logged' => Container::get('user')->logged));
                        break;

                    default:
                        DB::for_table('online')->raw_execute('INSERT INTO '.ForumSettings::get('db_prefix').'online (user_id, ident, logged) SELECT :user_id, :ident, :logged WHERE NOT EXISTS (SELECT 1 FROM '.$this->app->db->prefix.'online WHERE user_id=:user_id)', array(':user_id' => Container::get('user')->id, ':ident' => Container::get('user')->username, ':logged' => Container::get('user')->logged));
                        break;
                }

                // Reset tracked topics
                Track::set_tracked_topics(null);

            } else {
                // Special case: We've timed out, but no other user has browsed the forums since we timed out
                if (Container::get('user')->logged < (Container::get('now')-ForumSettings::get('o_timeout_visit'))) {
                    DB::for_table('users')->where('id', Container::get('user')->id)
                        ->find_one()
                        ->set('last_visit', Container::get('user')->logged)
                        ->save();
                    Container::get('user')->last_visit = Container::get('user')->logged;
                }

                $idle_sql = (Container::get('user')->idle == '1') ? ', idle=0' : '';

                DB::for_table('online')->raw_execute('UPDATE '.ForumSettings::get('db_prefix').'online SET logged='.Container::get('now').$idle_sql.' WHERE user_id=:user_id', array(':user_id' => Container::get('user')->id));

                // Update tracked topics with the current expire time
                $cookie_tracked_topics = Container::get('cookie')->get(ForumSettings::get('cookie_name').'_track');
                if (isset($cookie_tracked_topics)) {
                    Track::set_tracked_topics(json_decode($cookie_tracked_topics, true));
                }
            }
        } else {
            if (!Container::get('user')->logged) {
                Container::get('user')->logged = Container::get('user')->last_visit;
            }
        }
    }

    public function update_users_online()
    {
        // Fetch all online list entries that are older than "o_timeout_online"
        $select_update_users_online = array('user_id', 'ident', 'logged', 'idle');

        $result = DB::for_table('online')
                    ->select_many($select_update_users_online)
                    ->where_lt('logged', Container::get('now')-ForumSettings::get('o_timeout_online'))
                    ->find_many();

        foreach ($result as $cur_user) {
            // If the entry is a guest, delete it
            if ($cur_user['user_id'] == '1') {
                DB::for_table('online')->where('ident', $cur_user['ident'])
                    ->delete_many();
            } else {
                // If the entry is older than "o_timeout_visit", update last_visit for the user in question, then delete him/her from the online list
                if ($cur_user['logged'] < (Container::get('now')-ForumSettings::get('o_timeout_visit'))) {
                    DB::for_table('users')->where('id', $cur_user['user_id'])
                        ->find_one()
                        ->set('last_visit', $cur_user['logged'])
                        ->save();
                    DB::for_table('online')->where('user_id', $cur_user['user_id'])
                        ->delete_many();
                } elseif ($cur_user['idle'] == '0') {
                    DB::for_table('online')->where('user_id', $cur_user['user_id'])
                        ->update_many('idle', 1);
                }
            }
        }
    }

    public function check_bans()
    {
        global $feather_bans;

        // Admins and moderators aren't affected
        if (Container::get('user')->is_admmod || !$feather_bans) {
            return;
        }

        // Add a dot or a colon (depending on IPv4/IPv6) at the end of the IP address to prevent banned address
        // 192.168.0.5 from matching e.g. 192.168.0.50
        $user_ip = Utils::getIp();
        $user_ip .= (strpos($user_ip, '.') !== false) ? '.' : ':';

        $bans_altered = false;
        $is_banned = false;

        foreach ($feather_bans as $cur_ban) {
            // Has this ban expired?
            if ($cur_ban['expire'] != '' && $cur_ban['expire'] <= time()) {
                DB::for_table('bans')->where('id', $cur_ban['id'])
                    ->delete_many();
                $bans_altered = true;
                continue;
            }

            if ($cur_ban['username'] != '' && utf8_strtolower(Container::get('user')->username) == utf8_strtolower($cur_ban['username'])) {
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
                DB::for_table('online')
                    ->where('ident', Container::get('user')->username)
                    ->delete_many();
                throw new Error(__('Ban message').' '.(($cur_ban['expire'] != '') ? __('Ban message 2').' '.strtolower($this->app->utils->format_time($cur_ban['expire'], true)).'. ' : '').(($cur_ban['message'] != '') ? __('Ban message 3').'<br /><br /><strong>'.Utils::escape($cur_ban['message']).'</strong><br /><br />' : '<br /><br />').__('Ban message 4').' <a href="mailto:'.Utils::escape(ForumSettings::get('o_admin_email')).'">'.Utils::escape(ForumSettings::get('o_admin_email')).'</a>.', 403);
            }
        }

        // If we removed any expired bans during our run-through, we need to regenerate the bans cache
        if ($bans_altered) {
            Container::get('cache')->store('bans', Cache::get_bans());
        }
    }

    public function maintenance_message()
    {
        // Deal with newlines, tabs and multiple spaces
        $pattern = array("\t", '  ', '  ');
        $replace = array('&#160; &#160; ', '&#160; ', ' &#160;');
        $message = str_replace($pattern, $replace, ForumSettings::get('o_maintenance_message'));

        return View::setPageInfo(array(
            'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Maintenance')),
            'msg'    =>    $message,
            'backlink'    =>   false,
        ))->addTemplate('maintenance.php')->display();
    }

    public function __invoke($req, $res, $next)
    {
        // setcookie(ForumSettings::get('cookie_name'), '', 1, '/', '', false, true);
        global $feather_bans;

        $authCookie = Container::get('cookie')->get(ForumSettings::get('cookie_name'));

        if ($jwt = $this->get_cookie_data($authCookie)) {
            $user = AuthModel::load_user($jwt->data->userId);
            $expires = ($jwt->exp > Container::get('now') + ForumSettings::get('o_timeout_visit')) ? Container::get('now') + 1209600 : Container::get('now') + ForumSettings::get('o_timeout_visit');
            $user->is_guest = false;
            $user->is_admmod = $user->g_id == ForumEnv::get('FEATHER_ADMIN') || $user->g_moderator == '1';
            if (!$user->disp_topics) {
                $user->disp_topics = ForumSettings::get('o_disp_topics_default');
            }
            if (!$user->disp_posts) {
                $user->disp_posts = ForumSettings::get('o_disp_posts_default');
            }
            if (!file_exists(ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.$user->language)) {
                $user->language = ForumSettings::get('o_default_lang');
            }
            if (!file_exists(ForumEnv::get('FEATHER_ROOT').'style/themes/'.$user->style.'/style.css')) {
                $user->style = ForumSettings::get('o_default_style');
            }

            // Refresh cookie to avoid re-logging between idle
            $jwt = AuthModel::generate_jwt($user, $expires);
            AuthModel::feather_setcookie('Bearer '.$jwt, $expires);
            // Add ûser to DIC
            Container::set('user', $user);
            $this->update_online();
        } else {
            $user = AuthModel::load_user(1);

            $user->disp_topics = ForumSettings::get('o_disp_topics_default');
            $user->disp_posts = ForumSettings::get('o_disp_posts_default');
            $user->timezone = ForumSettings::get('o_default_timezone');
            $user->dst = ForumSettings::get('o_default_dst');
            $user->language = ForumSettings::get('o_default_lang');
            $user->style = ForumSettings::get('o_default_style');
            $user->is_guest = true;
            $user->is_admmod = false;

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
                    DB::for_table('online')->raw_execute('REPLACE INTO '.ForumSettings::get('db_prefix').'online (user_id, ident, logged) VALUES(1, :ident, :logged)', array(':ident' => Utils::getIp(), ':logged' => $user->logged));
                        break;

                    default:
                        DB::for_table('online')->raw_execute('INSERT INTO '.ForumSettings::get('db_prefix').'online (user_id, ident, logged) SELECT 1, :ident, :logged WHERE NOT EXISTS (SELECT 1 FROM '.ForumSettings::get('db_prefix').'online WHERE ident=:ident)', array(':ident' => Utils::getIp(), ':logged' => $user->logged));
                        break;
                }
            } else {
                DB::for_table('online')->where('ident', Utils::getIp())
                     ->update_many('logged', time());
            }
            // $jwt = AuthModel::generate_jwt($user, Container::get('now') + 31536000);
            // AuthModel::feather_setcookie('Bearer '.$jwt, Container::get('now') + 31536000);
            // Add $user as guest to DIC
            Container::set('user', $user);
        }

        load_textdomain('featherbb', ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.$user->language.'/common.mo');
        // Load bans from cache
        if (!Container::get('cache')->isCached('bans')) {
            Container::get('cache')->store('bans', Cache::get_bans());
        }
        $feather_bans = Container::get('cache')->retrieve('bans');

        // Check if current user is banned
        $this->check_bans();

        // Update online list
        $this->update_users_online();

        return $next($req, $res);
    }
}
