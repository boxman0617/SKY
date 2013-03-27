<?php
class MongoTest
{
	public function __construct()
	{
        unlink(DIR_APP_MODELS.'/Testpersons.model.php');
        $m = new MongoClient('mongodb://localhost');
        $db = $m->skytest;
        $db->drop();

		$class = '<?php
class Testpersons extends Model
{
    protected $DatabaseOverwrite    = array(
        "DB_SERVER"     => "localhost",
        "DB_USERNAME"   => "",
        "DB_PASSWORD"   => "",
        "DB_DATABASE"   => "skytest",
        "MODEL_DRIVER"  => "MongoDB"
    );
}
?>
';
        $f = fopen(DIR_APP_MODELS."/Testpersons.model.php", "w");
        fwrite($f, $class);
        fclose($f);
        $m = new MongoClient('mongodb://localhost');
        $db = $m->skytest;
        $c = $db->testpersons;
        $c->insert(array(
            'name' 		   => 'Alan',
        	'age' 		   => 23,
        	'occupation'   => 'Software Engineer',
            'created_at'   => new MongoDate(),
            'updated_at'   => new MongoTimestamp()
        ));
        $c->insert(array(
        	'name' 		   => 'Nancy',
        	'age' 		   => 27,
        	'occupation'   => 'Student',
            'created_at'   => new MongoDate(),
            'updated_at'   => new MongoTimestamp()
        ));
	}

	public function FindTest()
	{
		$m = new Testpersons();
		$r = $m->run();
        foreach($r as $doc)
        {
            TestMaster::AssertIsSet($doc->name, 'Name is not set?!');
            TestMaster::AssertIsSet($doc->age, 'Age is not set?!');
            TestMaster::AssertIsSet($doc->occupation, 'Occupation is not set?!');
        }
	}

    public function SaveTest()
    {
        $m = new Testpersons();
        $r = $m->find(array('name' => 'Alan'))->run();
        TestMaster::AssertEqual($r->age, 23, 'Document age not set to 23');
        $r->age = 24;
        TestMaster::AssertEqual($r->age, 24, 'Document age not set to 24');
        $r->save();

        $m = new Testpersons();
        $r = $m->find(array('name' => 'Alan'))->run();
        TestMaster::AssertEqual($r->age, 24, 'Document age not set to 24 after updating');

        $m = new Testpersons();
        TestMaster::AssertIsNotSet($m->name, 'Document has name in blank object');
        $m->name        = 'Bob';
        $m->age         = 21;
        $m->occupation  = 'Tester';
        $id = $m->save();

        $r->id;

        $m = new Testpersons();
        $r = $m->find(array('name' => 'Bob'))->run();
        TestMaster::AssertEqual($r->_id, $id, 'Returned _id from insert does not match! ['.$r->_id.']['.$id.']');
    }

    public function DeleteRow()
    {
        $m = new Testpersons();
        $r = $m->find()->run();
        $m = new Testpersons();
        $r = $m->find(array('name' => 'Alan'))->run();
        $BOOL = $r->delete();
        TestMaster::Assert($BOOL, 'Was not deleted!');
    }

    public function LoadTestSaving()
    {
        $m = new Testpersons();
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
        echo ($_END - $_START)." TIME\n";
    }
}
?>