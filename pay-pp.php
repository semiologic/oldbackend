<?php
$GLOBALS['cmd'] = 'pay_pp';

if ( empty($_POST) && !empty($_GET) )
{
	$_POST = $_GET;
	$_GET = array();
}

include dirname(__FILE__) . '/index.php';

validate_payment();
?>