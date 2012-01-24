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
     * Error Class Object
     * @access private
     * @var object
     */
    private $error;
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
    private $smarty_assign = array();
    /**
     * Smarty Instance
     * @access private
     * @var object
     */
    private $smarty_instance;
    /**
     * Method to render
     * @access private
     * @var string
     */
    private $method_name;
    
    protected $params;
    
    public function __construct($params)
    {
        Event::PublishActionHook('/Mailer/before/__construct/', array($this));
        $this->error = ErrorHandler::Singleton(true);
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
        foreach($this->smarty_assign as $key => $value)
        {
            $this->smarty_instance->assign($key, $value);
        }
        
        $message = $this->smarty_instance->fetch(strtolower(get_class($this)).'/'.strtolower($this->method_name).'.view.sky', null, null, null, false);
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
        $headers .= $email->header;
        $email->header = $headers;
        $email->body = $message;
        $email->from = $this->from;
        if(!$email->Send())
            $this->error->Toss('Email did not send!', E_USER_ERROR);
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
        $this->StartSmarty();
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
        $this->smarty_assign[$name] = $value;
        Event::PublishActionHook('/Mailer/after/Assign/', array($this, $name, $value));
    }
    
    /**
     * Initializes Smarty class, sets up {@link $smarty_instance}
     * @access protected
     */
    protected function StartSmarty()
    {
        $smarty = new Smarty();
        
        $smarty->template_dir = SMARTY_TEMPLATE_DIR;
        $smarty->compile_dir = SMARTY_COMPILE_DIR;
        $smarty->config_dir = SMARTY_CONFIG_DIR;
        $smarty->cache_dir = SMARTY_CACHE_DIR;
        
        $this->smarty_instance = $smarty;
    }
}
?>