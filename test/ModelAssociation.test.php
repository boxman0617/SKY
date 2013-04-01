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
}
?>