<?php
/**
 *
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 *
 * $app = new \Slim\Slim();
 * $app->add(new \Slim\Extras\Middleware\FeatherBBAuth());
 *
 */

namespace Slim\Extras\Middleware;
use DB;

class FeatherBBAuth extends \Slim\Middleware
{
    public function __construct()
	{
    }

    public function authenticate()
    {
        $now = time();

        // Get FeatherBB cookie
        $cookie_raw = $this->app->getCookie($this->app->forum_settings['cookie_name']);
        // Check if cookie exists and is valid (getCookie method returns false if the data has been tampered locally so it can't decrypt the cookie);
        if (isset($cookie_raw)) {
            $cookie = json_decode($cookie_raw, true);
            $checksum = hash_hmac('sha1', $cookie['user_id'].$cookie['expires'], $this->app->forum_settings['cookie_seed'].'_checksum');
            // If cookie has a non-guest user, hasn't expired and is legit
            if ($cookie['user_id'] > 1 && $cookie['expires'] > $now && $checksum == $cookie['checksum']) {
                // Get user info from db
                $select_check_cookie = array('u.*', 'g.*', 'o.logged', 'o.idle');
                $where_check_cookie = array('u.id' => intval($cookie['user_id']));

                $result = DB::for_table('users')
                    ->table_alias('u')
                    ->select_many($select_check_cookie)
                    ->inner_join('groups', array('u.group_id', '=', 'g.g_id'), 'g')
                    ->left_outer_join('online', array('o.user_id', '=', 'u.id'), 'o')
                    ->where($where_check_cookie)
                    ->find_result_set();

                foreach ($result as $this->app->user);

                // Another security check, to prevent identity fraud by changing the user id in the cookie) (might be useless considering the strength of encryption)
                if (isset($this->app->user->id) && hash_hmac('sha1', $this->app->user->password, $this->app->forum_settings['cookie_seed'].'_password_hash') === $cookie['password_hash']) {
                    $expires = ($cookie['expires'] > $now + $this->app->forum_settings['o_timeout_visit']) ? $now + 1209600 : $now + $this->app->forum_settings['o_timeout_visit'];
                    $this->app->user->is_guest = false;
                    $this->app->user->is_admmod = $this->app->user->g_id == $this->app->forum_env['FEATHER_ADMIN'] || $this->app->g_moderator == '1';
                    if (!$this->app->user->disp_topics) {
                        $this->app->user->disp_topics = $this->app->forum_settings['o_disp_topics_default'];
                    }
                    if (!$this->app->user->disp_posts) {
                        $this->app->user->disp_posts = $this->app->forum_settings['o_disp_posts_default'];
                    }
                    if (!file_exists($this->app->forum_env['FEATHER_ROOT'].'lang/'.$this->app->user->language)) {
                        $this->app->user->language = $this->app->forum_settings['o_default_lang'];
                    }
                    if (!file_exists($this->app->forum_env['FEATHER_ROOT'].'style/'.$this->app->user->style.'.css')) {
                        $this->app->user->style = $this->app->forum_settings['o_default_style'];
                    }
                    feather_setcookie($this->app->user->id, $this->app->user->password, $expires);
                    $this->update_online();
                    return true;
                }
            }
        }

        // If there is no cookie, or cookie is guest or expired, let's reconnect.
        $expires = $now + 31536000; // The cookie expires after a year

        // Fetch guest user
        $select_set_default_user = array('u.*', 'g.*', 'o.logged', 'o.last_post', 'o.last_search');
        $where_set_default_user = array('u.id' => '1');

        $result = DB::for_table('users')
            ->table_alias('u')
            ->select_many($select_set_default_user)
            ->inner_join('groups', array('u.group_id', '=', 'g.g_id'), 'g')
            ->left_outer_join('online', array('o.ident', '=', $this->app->request->getIp()), 'o', true)
            ->where($where_set_default_user)
            ->find_result_set();

        if (!$result) {
            exit('Unable to fetch guest information. Your database must contain both a guest user and a guest user group.');
        }

        foreach ($result as $this->app->user);

        $this->app->user->disp_topics = $this->app->forum_settings['o_disp_topics_default'];
        $this->app->user->disp_posts = $this->app->forum_settings['o_disp_posts_default'];
        $this->app->user->timezone = $this->app->forum_settings['o_default_timezone'];
        $this->app->user->dst = $this->app->forum_settings['o_default_dst'];
        $this->app->user->language = $this->app->forum_settings['o_default_lang'];
        $this->app->user->style = $this->app->forum_settings['o_default_style'];
        $this->app->user->is_guest = true;
        $this->app->user->is_admmod = false;

        // Update online list
        if (!$this->app->user->logged) {
            $this->app->user->logged = time();

            // With MySQL/MySQLi/SQLite, REPLACE INTO avoids a user having two rows in the online table
            switch ($this->app->forum_settings['db_type']) {
                case 'mysql':
                case 'mysqli':
                case 'mysql_innodb':
                case 'mysqli_innodb':
                case 'sqlite':
                case 'sqlite3':
                DB::for_table('online')->raw_execute('REPLACE INTO '.$this->app->forum_settings['db_prefix'].'online (user_id, ident, logged) VALUES(1, :ident, :logged)', array(':ident' => $this->app->request->getIp(), ':logged' => $this->app->user->logged));
                    break;

                default:
                    DB::for_table('online')->raw_execute('INSERT INTO '.$this->app->forum_settings['db_prefix'].'online (user_id, ident, logged) SELECT 1, :ident, :logged WHERE NOT EXISTS (SELECT 1 FROM '.$this->app->db->prefix.'online WHERE ident=:ident)', array(':ident' => $this->app->request->getIp(), ':logged' => $this->app->user->logged));
                    break;
            }
        } else {
            DB::for_table('online')->where('ident', $this->app->request->getIp())
                 ->update_many('logged', time());
        }

        feather_setcookie(1, feather_hash(uniqid(rand(), true)), $expires);
        return true;
    }

    public function update_online()
    {
        $now = time();

        // Define this if you want this visit to affect the online list and the users last visit data
        if (!defined('FEATHER_QUIET_VISIT')) {
            // Update the online list
            if (!$this->app->user->logged) {
                $this->app->user->logged = $now;

                // With MySQL/MySQLi/SQLite, REPLACE INTO avoids a user having two rows in the online table
                switch ($this->app->forum_settings['db_type']) {
                    case 'mysql':
                    case 'mysqli':
                    case 'mysql_innodb':
                    case 'mysqli_innodb':
                    case 'sqlite':
                    case 'sqlite3':
                        DB::for_table('online')->raw_execute('REPLACE INTO '.$this->app->forum_settings['db_prefix'].'online (user_id, ident, logged) VALUES(:user_id, :ident, :logged)', array(':user_id' => $this->app->user->id, ':ident' => $this->app->user->username, ':logged' => $this->app->user->logged));
                        break;

                    default:
                        DB::for_table('online')->raw_execute('INSERT INTO '.$this->app->forum_settings['db_prefix'].'online (user_id, ident, logged) SELECT :user_id, :ident, :logged WHERE NOT EXISTS (SELECT 1 FROM '.$this->app->db->prefix.'online WHERE user_id=:user_id)', array(':user_id' => $this->app->user->id, ':ident' => $this->app->user->username, ':logged' => $this->app->user->logged));
                        break;
                }

                // Reset tracked topics
                set_tracked_topics(null);

            } else {
                // Special case: We've timed out, but no other user has browsed the forums since we timed out
                if ($this->app->user->logged < ($now-$this->app->forum_settings['o_timeout_visit'])) {
                    DB::for_table('users')->where('id', $this->app->user->id)
                        ->find_one()
                        ->set('last_visit', $this->app->user->logged)
                        ->save();
                    $this->app->user->last_visit = $this->app->user->logged;
                }

                $idle_sql = ($this->app->user->idle == '1') ? ', idle=0' : '';

                DB::for_table('online')->raw_execute('UPDATE '.$this->app->forum_settings['db_prefix'].'online SET logged='.$now.$idle_sql.' WHERE user_id=:user_id', array(':user_id' => $this->app->user->id));

                // Update tracked topics with the current expire time
                $cookie_tracked_topics = $this->app->getCookie($this->app->forum_settings['cookie_name'].'_track');
                if (isset($cookie_tracked_topics)) {
                    set_tracked_topics(json_decode($cookie_tracked_topics, true));
                }
            }
        } else {
            if (!$this->app->user->logged) {
                $this->app->user->logged = $this->app->user->last_visit;
            }
        }
    }

    public function update_users_online()
    {
        $now = time();

        // Fetch all online list entries that are older than "o_timeout_online"
        $select_update_users_online = array('user_id', 'ident', 'logged', 'idle');

        $result = \DB::for_table('online')->select_many($select_update_users_online)
            ->where_lt('logged', $now-$this->app->forum_settings['o_timeout_online'])
            ->find_many();

        foreach ($result as $cur_user) {
            // If the entry is a guest, delete it
            if ($cur_user['user_id'] == '1') {
                \DB::for_table('online')->where('ident', $cur_user['ident'])
                    ->delete_many();
            } else {
                // If the entry is older than "o_timeout_visit", update last_visit for the user in question, then delete him/her from the online list
                if ($cur_user['logged'] < ($now-$this->app->forum_settings['o_timeout_visit'])) {
                    \DB::for_table('users')->where('id', $cur_user['user_id'])
                        ->find_one()
                        ->set('last_visit', $cur_user['logged'])
                        ->save();
                    \DB::for_table('online')->where('user_id', $cur_user['user_id'])
                        ->delete_many();
                } elseif ($cur_user['idle'] == '0') {
                    \DB::for_table('online')->where('user_id', $cur_user['user_id'])
                        ->update_many('idle', 1);
                }
            }
        }
    }

    public function check_bans()
    {
        global $feather_bans;

        // Admins and moderators aren't affected
        if ($this->app->user->is_admmod || !$feather_bans) {
            return;
        }

        // Add a dot or a colon (depending on IPv4/IPv6) at the end of the IP address to prevent banned address
        // 192.168.0.5 from matching e.g. 192.168.0.50
        $user_ip = get_remote_address();
        $user_ip .= (strpos($user_ip, '.') !== false) ? '.' : ':';

        $bans_altered = false;
        $is_banned = false;

        foreach ($feather_bans as $cur_ban) {
            // Has this ban expired?
            if ($cur_ban['expire'] != '' && $cur_ban['expire'] <= time()) {
                \DB::for_table('bans')->where('id', $cur_ban['id'])
                    ->delete_many();
                $bans_altered = true;
                continue;
            }

            if ($cur_ban['username'] != '' && utf8_strtolower($this->app->user->username) == utf8_strtolower($cur_ban['username'])) {
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
                \DB::for_table('online')->where('ident', $this->app->user->username)
                    ->delete_many();
                message(__('Ban message').' '.(($cur_ban['expire'] != '') ? __('Ban message 2').' '.strtolower(format_time($cur_ban['expire'], true)).'. ' : '').(($cur_ban['message'] != '') ? __('Ban message 3').'<br /><br /><strong>'.feather_escape($cur_ban['message']).'</strong><br /><br />' : '<br /><br />').__('Ban message 4').' <a href="mailto:'.feather_escape($this->app->forum_settings['o_admin_email']).'">'.feather_escape($this->app->forum_settings['o_admin_email']).'</a>.', true, true, true);
            }
        }

        // If we removed any expired bans during our run-through, we need to regenerate the bans cache
        if ($bans_altered) {
            if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
                require FEATHER_ROOT.'include/cache.php';
            }

            generate_bans_cache();
        }
    }

    public function call()
    {
        global $feather_bans, $cookie_name, $cookie_seed;

        $this->authenticate();

        // Load cached bans
        if (file_exists($this->app->forum_env['FORUM_CACHE_DIR'].'cache_bans.php')) {
            include $this->app->forum_env['FORUM_CACHE_DIR'].'cache_bans.php';
        }

        if (!defined('FEATHER_BANS_LOADED')) {
            if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
                require $this->app->forum_env['FEATHER_ROOT'].'include/cache.php';
            }

            generate_bans_cache();
            require $this->app->forum_env['FORUM_CACHE_DIR'].'cache_bans.php';
        }

        // Check if current user is banned
        $this->check_bans();

        // Update online list
        $this->update_users_online();

        // Configure Slim
        $this->app->config('templates.path', (is_dir('style/'.$this->app->user->style.'/view')) ? FEATHER_ROOT.'style/'.$this->app->user->style.'/view' : FEATHER_ROOT.'view');
        $this->next->call();
    }
}
