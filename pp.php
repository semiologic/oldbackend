<?php
$cmd = 'pp';

if ( empty($_POST) && !empty($_GET) )
{
	$_POST = $_GET;
	$_GET = array();
}

include dirname(__FILE__) . '/index.php';

if ( validate_order() )
{
	display_order_details();
}
else
{
	display_order_error();
}
?>