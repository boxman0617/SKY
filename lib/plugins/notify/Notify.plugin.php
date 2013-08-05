<?php
class Notify
{
    public static $Settings = array(
        ':by_user' => true,
        ':user_model' => 'Users',
        ':notify_model' => 'NotifySettings',
        ':notify_fields' => array(
            'user_id'               => 'user_id',
            'restricted'            => 'restricted',
            'restricted_days'       => 'restricted_days',
            'restricted_hours_from' => 'restricted_hours_from',
            'restricted_hours_to'   => 'restricted_hours_to',
            'types'                 => 'types',
            'types_data'            => 'types_data'
        ),
        ':notification_types' => array(
            'email' => 'PushEMail',
            'sms'   => 'PushSMS',
            'push'  => 'PushOver'
        ),
        ':pushover_token' => null,
        ':restrict' => false,
        ':restrict_per_user' => false
    );
    
    public static $UserSettings;
    
    private static $_subscribers = array();
    
    public function Subscribe($hook)
    {
        self::$_subscribers[] = $hook;
    }
    
    public static function Publish($hook, $params, $user_id)
    {
        if(in_array($hook, self::$_subscribers))
        {
            if(self::$Settings[':by_user'])
            {
                $NS = new self::$Settings[':notify_model']();
                $UserSettings = $NS->findOne(array(
                    self::$Settings[':notify_fields']['user_id'] => $user_id
                ))->run();
                
                foreach($UserSettings->types as $type)
                {
                    $CLASS = 'Push'.strtoupper($type);
                    $obj = new $CLASS($params);
                    $obj->Deliver($UserSettings);
                }
            }
        }
    }
}

interface iNotifyPush
{
    public function __construct($params = array());
    public function Deliver($settings);
}

class PushEMAIL implements iNotifyPush
{
    protected $params = array();
    protected $defaults = array(
        'subject' => 'Notify System Message',
        'from' => 'notify@sky.com'
    );
    
    public function __construct($params = array())
    {
        $this->params = $params;
        foreach($this->defaults as $field => $value)
        {
            if(!isset($this->params[$field]))
                $this->params[$field] = $value;
        }
    }
    
    public function Deliver($settings)
    {
        $USERS = Notify::$Settings[':user_model'];
        $user = $USERS::FindById($settings->user_id);
        
        if($user->responds_to("email"))
        {
            mail($user->email, $this->params['title'], $this->params['message'], "From: ".$this->params['from']."\r\n");
        }
    }
}

class PushSMS implements iNotifyPush
{
    protected $params = array();
    
    public function __construct($params = array())
    {
        $this->params = $params;
    }
    
    public function Deliver($settings)
    {
        $DATA = Notify::$Settings[':notify_fields']['types_data'];
        $D = $settings->$DATA;
        $MODEL = $D['over'][':model'];
        $FIELD = $D['over'][':field'];
        $obj = new $MODEL();
        $user = $obj->findOne(array(
            'user_id' => $settings->user_id
        ))->run();
        return true;
    }
}

class PushOVER implements iNotifyPush
{
    protected $params = array();
    
    public function __construct($params = array())
    {
        $this->params = $params;
    }
    
    public function Deliver($settings)
    {
        $DATA = Notify::$Settings[':notify_fields']['types_data'];
        $D = $settings->$DATA;
        $MODEL = $D['over'][':model'];
        $FIELD = $D['over'][':field'];
        $obj = new $MODEL();
        $user = $obj->findOne(array(
            'user_id' => $settings->user_id
        ))->run();
        $OPS = array_merge(array(
                "token" => Notify::$Settings[':pushover_token'],
                "user" => $user->$FIELD
        ), $this->params);
        curl_setopt_array($ch = curl_init(), array(
            CURLOPT_URL => "https://api.pushover.net/1/messages.json",
            CURLOPT_POSTFIELDS => $OPS,
            CURLOPT_RETURNTRANSFER => 1
        ));
        $bleh = curl_exec($ch);
        curl_close($ch);
    }
}
?>