<?php
class ModelAssociation
{
	public function __construct()
	{
		Fixtures::Start('ModelAssociations');
	}

	public function TestingBelongsToLogic()
	{
		$o = new Orders();
		$r = $o->search(array('name' => 'Shirt'))->run();
		$SHOULD_BE = 'Bob';
		TestMaster::AssertEqual($r->customer->name, $SHOULD_BE, 'Should have been Bob!');
	}

	public function TestingHasOneLogic()
	{
		$s = new Suppliers();
		$r = $s->search(array('name' => 'Nancy'))->run();
		$SHOULD_BE = 'ojas8d9cja98jd';
		TestMaster::AssertEqual($r->account->account_number, $SHOULD_BE, 'Account number is not the same!');
	}

	public function TestingHasManyLogic()
	{
		$c = new Customers();
		$r = $c->search(array('name' => 'Bob'))->run();
		$ORDERS = &$c->orders;
		$SHOULD_BE = 3;
		$COUNT = count($ORDERS);
		TestMaster::AssertEqual($COUNT, $SHOULD_BE, 'Orders where more then '.$SHOULD_BE.' ['.$COUNT.']');
	}

	public function TestingHasManyThroughLogic()
	{
		$patient = new Patients();
		$pa = $patient->findOne(array('name' => 'Alex'))->run();

		$PHYSICIAN_NAME = $pa->physicians->name;

		$physician = new Physicians();
		$ph = $physician->findOne(array('name' => $PHYSICIAN_NAME))->run();

		$PATIENT_NAME = $ph->patients->name;

		TestMaster::AssertEqual($pa->name, $PATIENT_NAME, 'Patient name is not the equal!');
		TestMaster::AssertEqual($ph->name, $PHYSICIAN_NAME, 'Physician name is not the equal!');
	}

	public function TestingHasOneThroughLogic()
	{
		$s = new Suppliers();
		$r = $s->search(array('name' => 'Nancy'))->run();

		TestMaster::AssertEqual($r->account_history->credit_rating, 10, 'Nancy\'s credit rating is not 10!?');
	}

	public function TestingHasAndBelongsToManyLogic()
	{
		$a = new Assemblies();
		$ar = $a->search(array('name' => 'One'))->run();

		$p = new Parts();
		$pr = $p->findOne()->run();

		$parts = $ar->parts;
		TestMaster::AssertEqual($parts->part_number, $pr->part_number, 'Assembly\'s part number is not correct!');
	}

	public function TestingSelfJoins()
	{
		$e = new Employees();
		$r = $e->search(array('name' => 'Bob'))->run();

		$manager = $r->manager;
		TestMaster::AssertEqual($manager->name, 'Alan', 'Wrong manager!');

		$e =  new Employees();
		$r = $e->search(array('name' => $manager->name))->run();
		$subordinates = $r->subordinates;
		TestMaster::AssertEqual($subordinates->name, 'Bob', 'Wrong subordinate');
	}
}
?>