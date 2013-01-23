<?php
/**
 * Mailer Core Class
 *
 * This class handles EMails
 * Should be called in Controllers
 *
 * LICENSE:
 *
 * This file may not be redistributed in whole or significant part, or
 * used on a web site without licensing of the enclosed code, and
 * software features.
 * 
 * @author Alan Tirado <root@deeplogik.com>
 * @copyright 2012 DeepLogiK, All Rights Reserved
 * @license http://www.deeplogik.com/sky/legal/license
 * @link http://www.deeplogik.com/sky/index
 * @version 1.0
 * @package Sky.Core
 */

/**
 * Mailer class
 * Handles EMails using templates
 * @package Sky.Core.Mailer
 */
abstract class Mailer
{
    /**
     * Email From header
     * @access public
     * @var string
     */
    public $from = 'from@example.com';
    /**
     * Variables to pass to Smarty
     * @access private
     * @var array
     */
    private $variables = array();
    /**
     * Method to render
     * @access private
     * @var string
     */
    private $method_name;
    
    protected $params;
    
    public function __construct($params = array())
    {
        Event::PublishActionHook('/Mailer/before/__construct/', array($this));
        $this->params = $params;
        Event::PublishActionHook('/Mailer/after/__construct/', array($this));
    }
    
    /**
     * Sends out Email using Smarty template
     * @param Email object only $email
     * @access protected
     */
    protected function Mail(Email $email)
    {
        Event::PublishActionHook('/Mailer/before/Mail/', array($this, $email));
        foreach($this->variables as $key => $value)
        {
            $$key = $value;
        }

        ob_start();
        include_once(VIEW_DIR.'/'.strtolower(get_class($this)).'/'.strtolower($this->method_name).'.view.php');
        $message = ob_get_contents();
        ob_end_clean();

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
        $headers .= $email->header;
        $email->header = $headers;
        $email->body = $message;
        $email->from = $this->from;
        if(!$email->Send())
            $_r = true;
            //$this->error->Toss('Email did not send!', E_USER_ERROR);
        Event::PublishActionHook('/Mailer/after/Mail/', array($this, $email));
    }
    
    /**
     * Starts Smarty up and runs Mailer method
     * @access public
     * @param string $method
     */
    public function Deliver($method)
    {
        Event::PublishActionHook('/Mailer/before/Deliver/', array($this, $method));
        $this->method_name = $method;
        call_user_func(array($this, $method));
        Event::PublishActionHook('/Mailer/after/Deliver/', array($this, $method));
    }
    
    /**
     * Sets up {@link $smarty_assign}
     * @access protected
     */
    protected function Assign($name, $value)
    {
        Event::PublishActionHook('/Mailer/before/Assign/', array($this, $name, $value));
        $this->variables[$name] = $value;
        Event::PublishActionHook('/Mailer/after/Assign/', array($this, $name, $value));
    }
}
?>