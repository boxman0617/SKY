<?php
require_once(getenv('SKYCORE').'/core/utils/SKY.class.php');
SKY::LoadCore();
SkyL::Import(SkyDefines::Call('PROCESSMANAGER_CLASS'));
SkyL::Import(SkyDefines::Call('RUNNINGPROCESS_CLASS'));
$script = $argv[1];

$s = ProcessManager::DoesScriptExists($script);

SkyL::Import($s);
?>