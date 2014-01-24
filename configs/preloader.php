<?php
//Preloader of ObjectFacotry, Models, and Mailers
import(FACTORY_CLASS);
spl_autoload_register('autoload_classes');
function autoload_classes($class_name)
{
    if(is_file(DIR_APP_MODELS.'/'.$class_name.'.model.php')) // Check for model
    {
        Event::PublishActionHook('/autoload_classes/before/model/', array($class_name));
        import(DIR_APP_MODELS.'/'.$class_name.'.model.php');
        Event::PublishActionHook('/autoload_classes/after/model/', array($class_name));
        return true;
    }
    if(is_file(DIR_APP_MAILERS.'/'.strtolower($class_name).'.mailer.php')) // Check for mailer
    {
        Event::PublishActionHook('/autoload_classes/before/mailer/', array($class_name));
        import(DIR_APP_MAILERS.'/'.strtolower($class_name).'.mailer.php');
        Event::PublishActionHook('/autoload_classes/after/mailer/', array($class_name));
        return true;
    }
    $class = ObjectFactory::Manufactor($class_name);
    if($class)
    {
        Event::PublishActionHook('/autoload_classes/before/Object/', array($class_name));
        return $class;
    }
    if(function_exists('LastChanceLoading'))
    {
        LastChanceLoading($class_name);
    }
    return false;
}
?>