<?php
class HomeMailer extends Mailer
{
    public $from = 'test@sky.com';
    
    public function TestEmail()
    {
        $this->Assign('name', $this->params['name']);
        $this->Assign('article', $this->params['article']);
        $email = new Email();
        $email->to = 'atirado@trinnovations.com';
        $email->subject = 'test';
        //$this->Mail($email);
    }
}
?>