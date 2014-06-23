<?php
class HomeMailer extends Mailer
{
    public $from = 'test@sky.com';

    public function TestEmail()
    {
        $this->Assign('name', $this->params['name']);
        $this->Assign('article', $this->params['article']);
        $email = new Email();
        $email->to = 'test@test.com'; // Change to send to yourself
        $email->subject = 'test';
        // Uncomment the line below to send email
        //$this->Mail($email);
    }
}
