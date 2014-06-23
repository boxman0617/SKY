<?php
class CheckPHPBin implements Task
{
	public function DeclareDependencies()
	{
		TaskManager::DependentOn('BlahBlah', 'DepOfBlahBlah');
		TaskManager::DependentOn('DepOfBlahBlah', 'DepOfDep');

		TaskManager::DependentOn('Bye', 'CheckBye');
	}

	public function BlahBlah()
	{

	}

	public function DepOfBlahBlah() 
	{
		
	}

	public function DepOfDep()
	{

	}

	public function HelloWorld()
	{

	}

	public function Bye()
	{

	}

	public function CheckBye()
	{

	}

	private function Haha() {}
}
