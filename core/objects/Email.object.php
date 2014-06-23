<?php
interface iEmail
{
    public function __set($name, $value);
    public function __get($name);
    public function Send();
}

class Email implements iEmail
{
    public $data = array();
    
    public function __construct()
    {
        
    }
    
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }
    
    public function __get($name)
    {
        if($name == 'header')
            $this->data['header'] = '';
        return $this->data[$name];
    }
    
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }
    
    public function Send()
    {
        $r = $this->CheckEmail();
        if($r)
        {
            return mail($this->data['to'], $this->data['subject'], $this->data['body'], "From: ".$this->data['from']."\r\n".$this->data['header']);
        }
        return false;
    }
    
    private function CheckEmail()
    {
        if(!isset($this->to))
        {
            trigger_error('No [to] field set', E_USER_ERROR);
            return false;
        }
        if(!isset($this->subject))
        {
            $this->data['subject'] = '';
        }
        if(!isset($this->body))
        {
            trigger_error('No [body] field set', E_USER_ERROR);
            return false;
        }
        if(!isset($this->header))
        {
            $this->data['header'] = '';
        }
        return true;
    }
}
