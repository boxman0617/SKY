<?php
Fixture::CreateModels('MySQL', array(
	'suppliers' => array(
		'name' => 'varchar_255'
	),
	'accounts' => array(
		'supplier_id' => 'int_11',
		'account_number' => 'varchar_255'
	),
	'orders' => array(
		'customer_id' => 'int_11',
		'name' => 'varchar_255'
	),
	'customers' => array(
		'name' => 'varchar_255'
	)
));

Fixture::CreateAssociations(array(
	'supplier' => array(
		'has_one' => array('account')
	),
	'order' => array(
		'belongs_to' => array('customer')
	)
));

$supplier_alan = Fixture::AddRow('suppliers', array(
	'name' => 'Alan'
));

$supplier_nancy = Fixture::AddRow('suppliers', array(
	'name' => 'Nancy'
));

$account_for_alan = Fixture::AddRow('accounts', array(
	'supplier_id' => $supplier_alan->id,
	'account_number' => 'iuh8237hf23jf2'
));

$account_for_nancy = Fixture::AddRow('accounts', array(
	'supplier_id' => $supplier_nancy->id,
	'account_number' => 'ojas8d9cja98jd'
));

$customer_bob = Fixture::AddRow('customers', array(
	'name' => 'Bob'
));

$order_for_bob = Fixture::AddRow('orders', array(
	'name' => 'Shirt',
	'customer_id' => $customer_bob->id
));
?>