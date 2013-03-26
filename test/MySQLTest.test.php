<?php
class MySQLTest
{
	public function __construct()
	{
		unlink(DIR_APP_MODELS.'/Mysqltests.model.php');
		$db = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
		$db->query('DROP TABLE `mysqltests`');
		$a = array(
			'MySQLTest.test.php',
			'mysqltests',
			'name:varchar_255',
			'age:int_11',
			'occupation:varchar_255'
		);
		$b = new DBBuild($a);
		$b->HandleInput();
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

	private function randVowel()
	{
		$vowels = array("a", "e", "i", "o", "u");
		return $vowels[array_rand($vowels, 1)];
	}

	private function randConsonant()
	{
		$consonants = array("a", "b", "c", "d", "v", "g", "t");
		return $consonants[array_rand($consonants, 1)];
	}

	public function LoadTestSaving()
	{
		$m = new Mysqltests();
		for($i = 0; $i < 100; $i++)
		{
			$m[$i]->name = ucfirst($this->randConsonant().$this->randVowel().$this->randConsonant().$this->randVowel().$this->randVowel());
			$m[$i]->age = rand(1, 100);
			$m[$i]->occupation = 'Person';
		}
		$m->save_all();
	}
}
?>