<?php
import(SESSION_CLASS);

interface iAuth
{
    public function __construct();
    public function LogIn($username, $password);
    public function LogOut();
    public static function IsLoggedIn();
    public function WhoAmI();
}

class Auth implements iAuth
{
    private $session;
    private $user_model = 'user';
    private $map = array(
        'username' => 'username',
        'password' => 'password'
    );

    public function __construct()
    {
        $this->session = Session::getInstance();
        $this->user_model = ucfirst(AUTH_MODEL);
        $this->map['username'] = AUTH_MODEL_USERNAME;
        $this->map['password'] = AUTH_MODEL_PASSWORD;
    }

    public function LogIn($username, $password)
    {
        Log::corewrite('Logging in [%s] [%s]', 3, __CLASS__, __FUNCTION__, array($username, md5(AUTH_SALT.$password)));
        $map_username = $this->map['username'];
        $map_password = $this->map['password'];
        $class = ucfirst($this->user_model);
        $user = new $class();
        $r = $user->where($map_username.' = ?', $username)->run();
        if(isset($r->$map_username) && $r->$map_username != null)
        {
            $encrypt_pass = md5(AUTH_SALT.$password);
            if($encrypt_pass == $r->$map_password)
            {
                Log::corewrite('Login was successful!', 1, __CLASS__, __FUNCTION__);
                $this->session->user_id = $r->id;
            } else {
                Log::corewrite('Login was not successful!', 1, __CLASS__, __FUNCTION__);
                $this->session->destroy();
                return false;
            }
            return true;
        } else {
            Log::corewrite('Login was not successful!', 1, __CLASS__, __FUNCTION__);
            $this->session->destroy();
            return false;
        }
    }

    public function LogOut()
    {
        $this->session->destroy();
        return true;
    }

    public static function IsLoggedIn()
    {
        return isset(Session::getInstance()->user_id);
    }

    public function WhoAmI()
    {
        if($this->IsLoggedIn())
        {
            $user = new $this->user_model();
            $map_username = $this->map['username'];
            $r = $user->where('id = ?', $this->session->user_id)->run();
            return array(
                'id' => $this->session->user_id,
                $map_username => $r->$map_username
            );
        }
    }
}
?>