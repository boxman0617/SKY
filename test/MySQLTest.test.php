<?php
class MySQLTest
{
	public function __construct()
	{
		Fixtures::Start('MySQLTest');
	}

	public function InsertRowsIntoTable()
	{
		$m = new Mysqltests();
		$m->name = 'Alan';
		$m->age = 23;
		$m->occupation = 'Software Engineer';
		$r = $m->save();
		TestMaster::Assert(is_numeric($r), 'Did not save!');
		$m = new Mysqltests();
		$m->name = 'Nancy';
		$m->age = 27;
		$m->occupation = 'Student';
		$r = $m->save();
		TestMaster::Assert(is_numeric($r), 'Did not save!');
		$m = new Mysqltests();
		$r = $m->select('count(*) as count')->run();
		TestMaster::AssertEqual($r->count, 2, 'Something went wrong! Did not save 2 rows. ['.$r->count.']');
	}

	public function UpdatingRow()
	{
		$m = new Mysqltests();
		$r = $m->where('name = ?', 'Alan')->run();
		$r->age = 24;
		$a = $r->save();
		TestMaster::Assert($a, 'Did not updated row!');
	}

	public function GettingDataFromTable()
	{
		$m = new Mysqltests();
		$r = $m->where('age > 20')->run();
		foreach($r as $a)
		{
			TestMaster::AssertIsSet($a->name, 'No name?');
			TestMaster::AssertIsSet($a->age, 'No age?');
			TestMaster::AssertIsSet($a->occupation, 'No occupation?');
		}
	}

	public function DeleteRow()
	{
			$m = new Mysqltests();
			$r = $m->where('name = ?', 'Alan')->run();
			$BOOL = $r->delete();
			TestMaster::Assert($BOOL, 'Was not deleted!');
	}

	public function LoadTestSaving()
	{
		$m = new Mysqltests();
		for($i = 0; $i < 100; $i++)
		{
			$m[$i]->name = ucfirst(randConsonant().randVowel().randConsonant().randVowel().randVowel());
			$m[$i]->age = rand(1, 100);
			$m[$i]->occupation = 'Person';
		}
		$_START = microtime(true);
		$RETURN = $m->save_all();
			$_END = microtime(true);
			TestMaster::Assert($RETURN, 'Something went wrong!');
			TestMaster::Assert((10 > ($_END - $_START)), 'Query took too long! ['.($_END - $_START).'s]');
	}

	public function LoadTestUpdating()
	{
		$m = new Mysqltests();
		$r = $m->run();
		$c = count($r);
		for($i = 0; $i < $c; $i++)
			$r[$i]->occupation = 'Alien';
		$_START = microtime(true);
		$RETURN = $r->save_all();
		$_END = microtime(true);
		TestMaster::Assert($RETURN, 'Something went wrong!');
		TestMaster::Assert((10 > ($_END - $_START)), 'Query took too long! ['.($_END - $_START).'s]');
	}

	public function LoadTestDeleteAll()
	{
			$m = new Mysqltests();
			$r = $m->run();
			$RETURN = $r->delete_all();
			TestMaster::Assert($RETURN, 'Something went wrong!');
	}
}
