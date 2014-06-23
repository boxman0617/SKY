<?php
Event::SubscribeActionHook('/autoload_classes/apend/special/', 'LoadSkyAuth');
Event::SubscribeActionHook('/Controller/before/HandleRequest/', 'SkyAuth::Protect');
Event::SubscribeActionHook('/Router/before/ControllerInit/', 'SkyAuth::AssertAuthorized');

function DefineAsSkyAuthUser()
{
    SkyL::Import(SkyDefines::Call('SKYCORE_LIB').'/plugins/skyauth/'.Plugin::$plugin['skyauth']['interface']);
}

function LoadSkyAuth($class_name = false)
{
    if($class_name !== false)
    {
        if($class_name != Plugin::$plugin['skyauth']['class'])
            return false;
    }
    SkyL::Import(SkyDefines::Call('SKYCORE_LIB').'/plugins/skyauth/'.Plugin::$plugin['skyauth']['file']);
    $ConfigFile = SkyDefines::Call('DIR_LIB_PLUGINS').'/skyauth/'.Plugin::$plugin['skyauth']['configfile'];
    if(file_exists($ConfigFile))
    {
        SkyL::Import($ConfigFile);
        SkyAuth::$Settings[':ENV'] = array_merge(SkyAuth::$Settings[':ENV'], $_AUTH[SkyDefines::GetEnv()]);
        SkyAuth::$AccessControl = $_ACCESS_CONTROL;
    }
}
