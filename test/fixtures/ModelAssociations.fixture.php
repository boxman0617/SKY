<?php
Fixture::CreateModels('MySQL', array(
	'suppliers' => array(
		'name' => 'varchar_255'
	),
	'accounts' => array(
		'supplier_id' => 'int_11',
		'account_number' => 'varchar_255'
	),
	'account_histories' => array(
		'account_id' => 'int_11',
		'credit_rating' => 'int_11'
	),
	'orders' => array(
		'customer_id' => 'int_11',
		'name' => 'varchar_255'
	),
	'customers' => array(
		'name' => 'varchar_255'
	),
	'physicians' => array(
		'name' => 'varchar_255'
	),
	'appointments' => array(
		'physician_id' => 'int_11',
		'patient_id' => 'int_11',
		'appointment_number' => 'varchar_255' 
	),
	'patients' => array(
		'name' => 'varchar_255'
	)
));

Fixture::CreateAssociations(array(
	'supplier' => array(
		'has_one' => array(
			'account' => true,
			'account_history' => array(':through' => 'account')
		)
	),
	'account' => array(
		'belongs_to' => array('supplier' => true),
		'has_one' => array('account_history' => true)
	),
	'account_history' => array(
		'belongs_to' => array('account' => true)
	),
	'order' => array(
		'belongs_to' => array('customer' => true)
	),
	'customer' => array(
		'has_many' => array('orders' => true)
	),
	'physician' => array(
		'has_many' => array(
			'appointments' => true,
			'patients' => array(':through' => 'appointments')
		)
	),
	'appointment' => array(
		'belongs_to' => array(
			'physician' => true,
			'patient' => true
		)
	),
	'patient' => array(
		'has_many' => array(
			'appointments' => true,
			'physicians' => array(':through' => 'appointments')
		)
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

Fixture::AddRow('account_histories', array(
	'account_id' => $account_for_alan->id,
	'credit_rating' => 5
));

Fixture::AddRow('account_histories', array(
	'account_id' => $account_for_nancy->id,
	'credit_rating' => 10
));

$customer_bob = Fixture::AddRow('customers', array(
	'name' => 'Bob'
));

Fixture::AddRow('orders', array(
	'name' => 'Shirt',
	'customer_id' => $customer_bob->id
));

Fixture::AddRow('orders', array(
	'name' => 'Jeens',
	'customer_id' => $customer_bob->id
));

Fixture::AddRow('orders', array(
	'name' => 'Shoes',
	'customer_id' => $customer_bob->id
));

// HasMany Through
$physician_joe = Fixture::AddRow('physicians', array(
	'name' => 'Joe'
));

$patient_alex = Fixture::AddRow('patients', array(
	'name' => 'Alex'
));

Fixture::AddRow('appointments', array(
	'physician_id' => $physician_joe->id,
	'patient_id' => $patient_alex->id,
	'appointment_number' => 'kn98sdca98sjd9ja'
));
?>