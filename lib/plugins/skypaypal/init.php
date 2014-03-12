<?php
function LoadSkyPayPal()
{
    SkyL::Import(SkyDefines::Call('SKYCORE_LIB').'/plugins/skypaypal/SkyPayPal.plugin.php');
    if(file_exists(SkyDefines::Call('DIR_LIB_PLUGINS').'/skypaypal/config.php'))
    {
        SkyL::Import(SkyDefines::Call('DIR_LIB_PLUGINS').'/skypaypal/config.php');
        SkyPayPal::$Settings[':ENV'] = array_merge(SkyPayPal::$Settings[':ENV'], $_PAYPAL[SkyDefines::GetEnv()]);
    }
}
?>