<?php

/**
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Random;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;
use Firebase\JWT\JWT;

class Auth
{
    public static function loadUser($userId)
    {
        $userId = (int) $userId;
        $result['select'] = ['u.*', 'g.*', 'o.logged', 'o.idle'];
        $result['where'] = ['u.id' => $userId];
        $result['join'] = ($userId == 1) ? Utils::getIp() : 'u.id';
        $escape = ($userId == 1) ? true : false;

        $result = DB::table('users')
                    ->tableAlias('u')
                    ->selectMany($result['select'])
                    ->innerJoin('groups', ['u.group_id', '=', 'g.g_id'], 'g')
                    ->leftOuterJoin('online', ['o.user_id', '=', $result['join']], 'o', $escape)
                    ->where($result['where'])
                    ->findOne();

        return $result;
    }

    public static function deleteOnlineByIP($ip)
    {
        $deleteOnline = DB::table('online')->where('ident', $ip);
        $deleteOnline = Hooks::fireDB('delete_online_login', $deleteOnline);
        return $deleteOnline->deleteMany();
    }

    public static function deleteOnlineById($userId)
    {
        // Remove user from "users online" list
        $deleteOnline = DB::table('online')->where('user_id', $userId);
        $deleteOnline = Hooks::fireDB('delete_online_logout', $deleteOnline);
        return $deleteOnline->deleteMany();
    }

    public static function getUserFromName($username)
    {
        $user = DB::table('users')->where('username', $username);
        $user = Hooks::fireDB('find_user_login', $user);
        return $user->findOne();
    }

    public static function getUserFromEmail($email)
    {
        $result['select'] = ['id', 'username', 'last_email_sent'];
        $result = DB::table('users')
            ->selectMany($result['select'])
            ->where('email', $email);
        $result = Hooks::fireDB('password_forgotten_query', $result);
        return $result->findOne();
    }

    public static function updateGroup($userId, $groupId)
    {
        $updateUsergroup = DB::table('users')->where('id', $userId)
            ->findOne()
            ->set('group_id', $groupId);
        $updateUsergroup = Hooks::fireDB('update_usergroup_login', $updateUsergroup);
        return $updateUsergroup->save();
    }

    public static function setLastVisit($userId, $lastVisit)
    {
        $updateLastVisit = DB::table('users')->where('id', (int) $userId)
            ->findOne()
            ->set('last_visit', (int) $lastVisit);
        $updateLastVisit = Hooks::fireDB('update_online_logout', $updateLastVisit);
        return $updateLastVisit->save();
    }

    public static function setNewPassword($pass, $key, $userId)
    {
        $query['update'] = [
            'activate_string' => Utils::passwordHash($pass),
            'activate_key'    => $key,
            'last_email_sent' => time(),
        ];

        $query = DB::table('users')
                    ->where('id', $userId)
                    ->findOne()
                    ->set($query['update']);
        $query = Hooks::fireDB('password_forgotten_mail_query', $query);
        return $query->save();
    }

    public static function updatePassword($userId, $clearPassword)
    {
        $query = DB::table('users')
            ->where('id', $userId)
            ->findOne()
            ->set('password', Utils::passwordHash($clearPassword));
        $query = Hooks::fireDB('update_password_query', $query);
        return $query->save();
    }

    public static function generateJwt($user, $expire)
    {
        $issuedAt   = time();
        $tokenId    = base64_encode(Random::key(32));
        $serverName = Url::baseStatic();

        /*
        * Create the token as an array
        */
        $data = [
            'iat'  => $issuedAt,         // Issued at: time when the token was generated
            'jti'  => $tokenId,          // Json Token Id: an unique identifier for the token
            'iss'  => $serverName,       // Issuer
            'exp'  => $expire,           // Expire after 30 minutes of idle or 14 days if "remember me"
            'data' => [                  // Data related to the signer user
                'userId'   => $user->id, // userid from the users table
                'userName' => $user->username, // User name
            ]
        ];

        /*
        * Extract the key, which is coming from the config file.
        *
        * Generated with base64_encode(openssl_random_pseudo_bytes(64));
        */
        $secretKey = base64_decode(ForumEnv::get('JWT_TOKEN'));

        /*
        * Extract the algorithm from the config file too
        */
        $algorithm = ForumEnv::get('JWT_ALGORITHM');

        /*
        * Encode the array to a JWT string.
        * Second parameter is the key to encode the token.
        *
        * The output string can be validated at http://jwt.io/
        */
        $jwt = JWT::encode(
            $data,      //Data to be encoded in the JWT
            $secretKey, // The signing key
            $algorithm  // Algorithm used to sign the token, see https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40#section-3
        );

        return $jwt;
    }

    public static function setCookie($jwt, $expire)
    {
        // Store cookie to client storage
        setcookie(ForumEnv::get('COOKIE_NAME'), $jwt, $expire, '/', '', false, true);
    }
}
