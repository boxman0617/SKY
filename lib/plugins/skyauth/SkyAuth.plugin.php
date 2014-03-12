<?php
class SkyAuth
{
    public static $Settings = array(
        ':ENV' => array(
            'BcryptRounds'      => 12,
            'OnFailureRoute'    => null,
            'OnFailureFlash'    => null,
            'OnSuccessRoute'    => null,
            'Domain'            => null,
            'UserModel'         => 'Users',
            'ControllerProtectedPropertyName' => 'SkyAuthProtected'
        )
    );
    public static $AccessControl = array();
    public static $CurrentUser = null;
    
    private static $Success = false;
    private static $Logout = false;
    
    public static function Protect(Controller $controller)
    {
        $is_protected = self::$Settings[':ENV']['ControllerProtectedPropertyName'];
        if(property_exists($controller, $is_protected))
        {
            if($controller->$is_protected)
                return self::IsAuthenticated($controller);
        }
        return true;
    }
    
    // ## Use in VIEWS
    public static function IsAuthorized($rules = array())
    {
        if(array_key_exists('roles', $rules))
        {
            if(!in_array(
                self::GetCurrentUser()->GetRole(),
                $rules['roles']
            )) {
                return false;
            }
        }
        if(array_key_exists('groups', $rules))
        {
            if(!in_array(
                self::GetCurrentUser()->GetGroup(),
                $rules['groups']
            )) {
                return false;
            }
        }
        return true;
    }
    
    // ## Used in CONTROLLER
    public static function AssertAuthorized($info = array())
    {
        $is_protected = self::$Settings[':ENV']['ControllerProtectedPropertyName'];
        if(property_exists($info['controller'], $is_protected) && $info['controller']->$is_protected)
        {
            if(array_key_exists($info['class'], self::$AccessControl))
            {
                if(array_key_exists($info['action'], self::$AccessControl[$info['class']]))
                {
                    if(self::$AccessControl[$info['class']][$info['action']] === AUTH_ALLOW_ALL)
                    {
                        return true;
                    } elseif(self::$AccessControl[$info['class']][$info['action']] === AUTH_DENY_ALL) {
                        self::AccessDenied();
                    } else {
                        if(array_key_exists('roles', self::$AccessControl[$info['class']][$info['action']]))
                        {
                            if(in_array(
                                self::GetCurrentUser()->GetRole(), 
                                self::$AccessControl[$info['class']][$info['action']]['roles']))
                            {
                                return true;
                            }
                        }
                        if(array_key_exists('groups', self::$AccessControl[$info['class']][$info['action']]))
                        {
                            if(in_array(
                                self::GetCurrentUser()->GetGroup(), 
                                self::$AccessControl[$info['class']][$info['action']]['groups']))
                            {
                                return true;
                            }
                        }
                    }
                }
            }
            self::AccessDenied();
        }
        return true;
    }
    
    private static function AccessDenied()
    {
        require_once(DIR_LIB_PLUGINS.'/skyauth/'.Plugin::$plugin['skyauth']['denypage']);
        exit();
    }
    
    public static function GetCurrentUser()
    {
        if(is_null(self::$CurrentUser))
        {
            $session = Session::getInstance();
            if(isset($session->skyauth))
            {
                if(!array_key_exists(self::$Settings[':ENV']['Domain'], $session->skyauth))
                    return false;
                if(array_key_exists('user_id', $session->skyauth[self::$Settings[':ENV']['Domain']]))
                {
                    $user = call_user_func(
                        self::$Settings[':ENV']['UserModel'].'::FindOneById', 
                        $session->skyauth[self::$Settings[':ENV']['Domain']]['user_id']
                    );
                    if(!$user->is_empty())
                        self::$CurrentUser = $user;
                    else
                        return false;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
        
        return self::$CurrentUser;
    }
    
    public static function CreateUser(SkyAuthUser $user)
    {
        $bcrypt = new Bcrypt(self::$Settings[':ENV']['BcryptRounds']);
        $user->SetPassword($bcrypt->hash($user->GetPassword()));
    }
    
    public static function IsAuthenticated(Controller $controller)
    {
        if(self::GetCurrentUser() === false)
            $controller->RedirectTo(self::$Settings[':ENV']['OnFailureRoute']);
        
        return true;
    }
    
    public static function TerminateSession()
    {
        $session = Session::getInstance();
        if(isset($session->skyauth))
        {
            $temp = $session->skyauth;
            if(isset($temp[self::$Settings[':ENV']['Domain']]))
            {
                unset($temp[self::$Settings[':ENV']['Domain']]);
                $session->skyauth = $temp;
                self::$Logout = true;
            }
        }
        
        Event::SubscribeActionHook('/Controller/atrender/HandleRequest/', 'SkyAuth::Redirect');
    }
    
    public static function Authenticate(SkyAuthUser $user, $input)
    {
        $bcrypt = new Bcrypt(self::$Settings[':ENV']['BcryptRounds']);
        if($bcrypt->verify($input, $user->GetPassword()))
            self::$Success = true;
        
        if(self::$Success)
        {
            self::$CurrentUser = $user;
            $session = Session::getInstance();
            if(!isset($session->skyauth))
                $session->skyauth = array();
            
            $temp = $session->skyauth;
            $temp[self::$Settings[':ENV']['Domain']] = array(
                'user_id' => $user->id
            );
            $session->skyauth = $temp;
        }
        Event::SubscribeActionHook('/Controller/atrender/HandleRequest/', 'SkyAuth::Redirect');
    }
    
    public static function Redirect(Controller $controller)
    {
        $REDIRECT = self::$Settings[':ENV']['OnFailureRoute'];
        if(self::$Logout === false)
        {
            if(self::$Success)
            {
                if(is_null(self::$Settings[':ENV']['OnSuccessRoute']))
                    throw new Exception('No route specified for SUCCESS status. Please do so in SkyAuth\'s config file.');
                $REDIRECT = self::$Settings[':ENV']['OnSuccessRoute'];
            } else {
                if(is_null(self::$Settings[':ENV']['OnFailureRoute']))
                    throw new Exception('No route specified for FAILURE status. Please do so in SkyAuth\'s config file.');
                $controller->SetFlash(self::$Settings[':ENV']['OnFailureFlash'], 'error');
            }
        }
        
        $controller->RedirectTo($REDIRECT);
    }
}

class Bcrypt 
{
    private $rounds;
    private $randomState;
    
    public function __construct($rounds = 12) 
    {
        if(CRYPT_BLOWFISH != 1)
            throw new Exception("bcrypt not supported in this installation. See http://php.net/crypt");
    
        $this->rounds = $rounds;
    }
    
    public function hash($input) 
    {
        $hash = crypt($input, $this->getSalt());
    
        if(strlen($hash) > 13)
            return $hash;
    
        return false;
    }
    
    public function verify($input, $existingHash) 
    {
        $hash = crypt($input, $existingHash);
    
        return $hash === $existingHash;
    }
    
    private function getSalt() 
    {
        $salt = sprintf('$2a$%02d$', $this->rounds);
    
        $bytes = $this->getRandomBytes(16);
    
        $salt .= $this->encodeBytes($bytes);
    
        return $salt;
    }
    
    private function getRandomBytes($count) 
    {
        $bytes = '';
    
        if(function_exists('openssl_random_pseudo_bytes') && (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')) 
        { // OpenSSL slow on Win
            $bytes = openssl_random_pseudo_bytes($count);
        }
    
        if($bytes === '' && is_readable('/dev/urandom') && ($hRand = @fopen('/dev/urandom', 'rb')) !== FALSE) 
        {
            $bytes = fread($hRand, $count);
            fclose($hRand);
        }
    
        if(strlen($bytes) < $count) 
        {
            $bytes = '';
    
            if($this->randomState === null) 
            {
                $this->randomState = microtime();
                if(function_exists('getmypid')) 
                {
                    $this->randomState .= getmypid();
                }
            }
    
            for($i = 0; $i < $count; $i += 16) 
            {
                $this->randomState = md5(microtime() . $this->randomState);
    
                if (PHP_VERSION >= '5') 
                {
                    $bytes .= md5($this->randomState, true);
                } else {
                    $bytes .= pack('H*', md5($this->randomState));
                }
            }
    
            $bytes = substr($bytes, 0, $count);
        }
    
        return $bytes;
    }
    
    private function encodeBytes($input) 
    {
        // The following is code from the PHP Password Hashing Framework
        $itoa64 = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        
        $output = '';
        $i = 0;
        do {
            $c1 = ord($input[$i++]);
            $output .= $itoa64[$c1 >> 2];
            $c1 = ($c1 & 0x03) << 4;
            if ($i >= 16) 
            {
                $output .= $itoa64[$c1];
                break;
            }
        
            $c2 = ord($input[$i++]);
            $c1 |= $c2 >> 4;
            $output .= $itoa64[$c1];
            $c1 = ($c2 & 0x0f) << 2;
        
            $c2 = ord($input[$i++]);
            $c1 |= $c2 >> 6;
            $output .= $itoa64[$c1];
            $output .= $itoa64[$c2 & 0x3f];
        } while (1);
        
        return $output;
    }
}
?>