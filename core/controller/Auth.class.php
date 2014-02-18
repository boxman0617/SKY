<?php
// ####
// Auth Class
// 
// This class handles a high level Authentication
// system based on Sessions.
//
// @license
// - This file may not be redistributed in whole or significant part, or
// - used on a web site without licensing of the enclosed code, and
// - software features.
//
// @author      Alan Tirado <alan@deeplogik.com>
// @copyright   2013 DeepLogik, All Rights Reserved
//
// @version     0.0.7 Starting from here
// ##

import(SESSION_CLASS);

interface iAuth
{
    public function __construct();
    public function LogIn($username, $password);
    public function LogOut();
    public static function IsLoggedIn($ident);
    public function WhoAmI();
}

// ####
// Auth Class
// @desc This class handles a high level Authentication
// @abstract
// @package SKY.Core.Auth
// ##
class Auth extends Base implements iAuth
{
    private $session;
    private $user_model = 'user';
    private $map = array(
        'username' => 'username',
        'password' => 'password'
    );

    public function __construct()
    {
        self::$_share['session'] = Session::getInstance();
        $this->user_model = ucfirst(AUTH_MODEL);
        $this->map['username'] = AUTH_MODEL_USERNAME;
        $this->map['password'] = AUTH_MODEL_PASSWORD;
    }

    public function LogIn($username, $password, $ident = 'user_id')
    {
        Log::corewrite('Logging in [%s] [%s]', 3, __CLASS__, __FUNCTION__, array($username, md5(AUTH_SALT.$password)));
        $map_username = $this->map['username'];
        $map_password = $this->map['password'];
        $class = ucfirst($this->user_model);
        $r = $class::Search(array(
            $map_username => $username
        ));
        if(isset($r->$map_username) && $r->$map_username != null)
        {
            $encrypt_pass = md5(AUTH_SALT.$password);
            if($encrypt_pass == $r->$map_password)
            {
                Log::corewrite('Login was successful!', 1, __CLASS__, __FUNCTION__);
                self::$_share['session']->$ident = $r->id;
                self::$_share['user'] = $r;
            } else {
                Log::corewrite('Login was not successful!', 1, __CLASS__, __FUNCTION__);
                self::$_share['session']->destroy();
                return false;
            }
            return true;
        } else {
            Log::corewrite('Login was not successful!', 1, __CLASS__, __FUNCTION__);
            self::$_share['session']->destroy();
            return false;
        }
    }

    public function LogOut()
    {
        self::$_share['session']->destroy();
        return true;
    }

    public static function IsLoggedIn($ident = 'user_id')
    {
        return isset(Session::getInstance()->$ident);
    }

    public function WhoAmI()
    {
        if(self::IsLoggedIn())
        {
            $user = new $this->user_model();
            $map_username = $this->map['username'];
            $r = $user->search(array($user->getPrimaryKey, self::$_share['session']->user_id))->run();
            return array(
                'id' => self::$_share['session']->user_id,
                $map_username => $r->$map_username
            );
        }
    }
}
?>