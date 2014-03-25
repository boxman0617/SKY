<?php
SkyL::Import(SkyDefines::Call('PROCESSMANAGER_CLASS'));
SkyL::Import(SkyDefines::Call('PROCESS_CLASS'));
class ProcessTest
{
	public function Start()
	{
		ProcessManager::Fork('HelloWorld');
	}
}
?>