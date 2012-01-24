<?php
import(FACTORY_CLASS);
spl_autoload_register('autoload_classes');
function autoload_classes($class_name)
{
    if(is_file(MODEL_DIR.'/'.$class_name.'.model.php')) // Check for model
    {
        Event::PublishActionHook('/autoload_classes/before/model/', array($class_name));
        import(MODEL_DIR.'/'.$class_name.'.model.php');
        return true;
    }
    if(is_file(MAILER_DIR.'/'.strtolower($class_name).'.mailer.php')) // Check for mailer
    {
        Event::PublishActionHook('/autoload_classes/before/mailer/', array($class_name));
        import(MAILER_DIR.'/'.strtolower($class_name).'.mailer.php');
        return true;
    }
    $class = ObjectFactory::Manufactor($class_name);
    if($class)
    {
        Event::PublishActionHook('/autoload_classes/before/Object/', array($class_name));
        return $class;
    }
    return false;
}
?>