<?php
class MySQLTest
{
	public function __construct()
	{
		$a = array(
			'MySQLTest.test.php',
			'mysqltests',
			'name:varchar_255',
			'age:int_11',
			'occupation:varchar_255'
		);
		$b = new DBBuild($a);
		$b->HandleInput();
		$db = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
		$db->query('INSERT INTO `mysqltests` (`name`, `age`, `occupation`) VALUES ("Alan", 23, "Web Developer")');
		$db->query('INSERT INTO `mysqltests` (`name`, `age`, `occupation`) VALUES ("Nancy", 27, "Student")');
		$db->query('INSERT INTO `mysqltests` (`name`, `age`, `occupation`) VALUES ("Bob", 21, "Farmer")');
	}

	public function SettingDriver()
	{
		$m = new Mysqltests();
		$r = $m->where('age > 20')->run();
		foreach($r as $a)
			var_dump($a);
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

	public function __destruct()
	{
		unlink(DIR_APP_MODELS.'/Mysqltests.model.php');
		$db = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
		$db->query('DROP TABLE `mysqltests`');
	}
}
?>