<?php

define('AUTH_ALLOW_ALL', true);
define('AUTH_DENY_ALL', false);

$_AUTH['DEV'] = array(
    'OnFailureRoute'    => '/',
    'OnSuccessRoute'    => '/',
    'OnFailureFlash'    => 'Unable to authenticate user. Please try again...',
    'Domain'            => 'YourDomainGoesHere'
);

$_ACCESS_CONTROL = array(
    'Dashboard' => array(
        'Main'          => AUTH_ALLOW_ALL
    )
);
