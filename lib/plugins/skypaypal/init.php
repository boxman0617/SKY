<?php
function LoadSkyPayPal()
{
    require_once(SKYCORE_LIB.'/plugins/skypaypal/SkyPayPal.plugin.php');
    if(file_exists(DIR_LIB_PLUGINS.'/skypaypal/config.php'))
    {
        require_once(DIR_LIB_PLUGINS.'/skypaypal/config.php');
        SkyPayPal::$Settings[':ENV'] = array_merge(SkyPayPal::$Settings[':ENV'], $_PAYPAL[$GLOBALS['ENV']]);
    }
}
?>