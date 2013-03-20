<?php
class MongoTest
{
	public function __construct()
	{
		$class = "<?php
class Testpersons extends Model
{

}
?>
";
        $f = fopen(DIR_APP_MODELS."/Testpersons.model.php", "w");
        fwrite($f, $class);
        fclose($f);
        $m = new MongoClient('mongodb://localhost');
        $d = $m->skytest;
        $c = $d->testpersons;
        $c->insert(array(
        	'name' 			=> 'Alan',
        	'age' 			=> 23,
        	'occupation' 	=> 'Software Engineer'
        ));
        $c->insert(array(
        	'name' 			=> 'Nancy',
        	'age' 			=> 27,
        	'occupation' 	=> 'Student'
        ));
	}

	public function FindTest()
	{
		$m = new Testpersons();
		$r = $m->run();
		var_dump($r);
	}

	public function __destruct()
	{
		unlink(DIR_APP_MODELS.'/Testpersons.model.php');
	}
}
?>