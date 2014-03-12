<?php
SkyL::Import(SkyDefines::Call('SKYCORE_LIB').'/plugins/notify/Notify.plugin.php');
$NOTIFY = new Notify();
if(file_exists(SkyDefines::Call('DIR_LIB_PLUGINS').'/notify/config.php'))
{
    SkyL::Import(SkyDefines::Call('DIR_LIB_PLUGINS').'/notify/config.php');
}
?>