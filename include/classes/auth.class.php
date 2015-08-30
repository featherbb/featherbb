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

namespace FeatherBB;
use DB;

class Auth extends \Slim\Middleware
{
    protected $model;

    public function __construct()
	{
        $this->model = new \model\auth();
    }

    public function get_cookie_data($cookie_name, $cookie_seed)
    {
        // Get FeatherBB cookie
        $cookie_raw = $this->app->getCookie($cookie_name);
        // Check if cookie exists and is valid (getCookie method returns false if the data has been tampered locally so it can't decrypt the cookie);
        if (isset($cookie_raw)) {
            $cookie = json_decode($cookie_raw, true);
            $checksum = hash_hmac('sha1', $cookie['user_id'].$cookie['expires'], $cookie_seed.'_checksum');
            if ($cookie['user_id'] > 1 && $cookie['expires'] > $this->app->now && $checksum == $cookie['checksum']) {
                return $cookie;
            }
        }
        return false;
    }

    public function update_online()
    {
        // Define this if you want this visit to affect the online list and the users last visit data
        if (!defined('FEATHER_QUIET_VISIT')) {
            // Update the online list
            if (!$this->app->user->logged) {
                $this->app->user->logged = $this->app->now;

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
                if ($this->app->user->logged < ($this->app->now-$this->app->forum_settings['o_timeout_visit'])) {
                    DB::for_table('users')->where('id', $this->app->user->id)
                        ->find_one()
                        ->set('last_visit', $this->app->user->logged)
                        ->save();
                    $this->app->user->last_visit = $this->app->user->logged;
                }

                $idle_sql = ($this->app->user->idle == '1') ? ', idle=0' : '';

                DB::for_table('online')->raw_execute('UPDATE '.$this->app->forum_settings['db_prefix'].'online SET logged='.$this->app->now.$idle_sql.' WHERE user_id=:user_id', array(':user_id' => $this->app->user->id));

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
        // Fetch all online list entries that are older than "o_timeout_online"
        $select_update_users_online = array('user_id', 'ident', 'logged', 'idle');

        $result = \DB::for_table('online')
                    ->select_many($select_update_users_online)
                    ->where_lt('logged', $this->app->now-$this->app->forum_settings['o_timeout_online'])
                    ->find_many();

        foreach ($result as $cur_user) {
            // If the entry is a guest, delete it
            if ($cur_user['user_id'] == '1') {
                \DB::for_table('online')->where('ident', $cur_user['ident'])
                    ->delete_many();
            } else {
                // If the entry is older than "o_timeout_visit", update last_visit for the user in question, then delete him/her from the online list
                if ($cur_user['logged'] < ($this->app->now-$this->app->forum_settings['o_timeout_visit'])) {
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
        $user_ip = $this->app->request->getIp();
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
                \DB::for_table('online')
                    ->where('ident', $this->app->user->username)
                    ->delete_many();
                throw new \FeatherBB\Error(__('Ban message').' '.(($cur_ban['expire'] != '') ? __('Ban message 2').' '.strtolower(format_time($cur_ban['expire'], true)).'. ' : '').(($cur_ban['message'] != '') ? __('Ban message 3').'<br /><br /><strong>'.$this->app->utils->escape($cur_ban['message']).'</strong><br /><br />' : '<br /><br />').__('Ban message 4').' <a href="mailto:'.$this->app->utils->escape($this->app->forum_settings['o_admin_email']).'">'.$this->app->utils->escape($this->app->forum_settings['o_admin_email']).'</a>.', 403);
            }
        }

        // If we removed any expired bans during our run-through, we need to regenerate the bans cache
        if ($bans_altered) {
            $this->app->cache->store('bans', \model\cache::get_bans());
        }
    }

    public function maintenance_message()
    {
        // Deal with newlines, tabs and multiple spaces
        $pattern = array("\t", '  ', '  ');
        $replace = array('&#160; &#160; ', '&#160; ', ' &#160;');
        $message = str_replace($pattern, $replace, $this->app->forum_settings['o_maintenance_message']);

        $this->app->view2->setPageInfo(array(
            'title' => array($this->app->utils->escape($this->app->forum_settings['o_board_title']), __('Maintenance')),
            'active_page' => 'index',
            'message'    =>    $message,
            'no_back_link'    =>    '',
        ))->addTemplate('message.php')->display();

        // Don't display anything after a message
        $this->app->stop();
    }

    public function call()
    {
        global $feather_bans;

        if ($cookie = $this->get_cookie_data($this->app->forum_settings['cookie_name'], $this->app->forum_settings['cookie_seed'])) {
            $this->app->user = $this->model->load_user($cookie['user_id']);
            $expires = ($cookie['expires'] > $this->app->now + $this->app->forum_settings['o_timeout_visit']) ? $this->app->now + 1209600 : $this->app->now + $this->app->forum_settings['o_timeout_visit'];
            $this->app->user->is_guest = false;
            $this->app->user->is_admmod = $this->app->user->g_id == $this->app->forum_env['FEATHER_ADMIN'] || $this->app->user->g_moderator == '1';
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
            $this->model->feather_setcookie($this->app->user->id, $this->app->user->password, $expires);
            $this->update_online();
        } else {
            $this->app->user = $this->model->load_user(1);

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

            $this->model->feather_setcookie(1, \FeatherBB\Utils::feather_hash(uniqid(rand(), true)), $this->app->now + 31536000);
        }

        load_textdomain('featherbb', $this->app->forum_env['FEATHER_ROOT'].'lang/'.$this->app->user->language.'/common.mo');

        // Load bans from cache
        if (!$this->app->cache->isCached('bans')) {
            $this->app->cache->store('bans', \model\cache::get_bans());
        }
        $feather_bans = $this->app->cache->retrieve('bans');

        // Check if current user is banned
        $this->check_bans();

        // Update online list
        $this->update_users_online();

        $this->next->call();
    }
}
