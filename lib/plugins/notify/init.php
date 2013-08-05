<?php
require_once(SKYCORE_LIB.'/plugins/notify/Notify.plugin.php');
$NOTIFY = new Notify();
if(file_exists(DIR_LIB_PLUGINS.'/notify/config.php'))
{
    require_once(DIR_LIB_PLUGINS.'/notify/config.php');
}
?>