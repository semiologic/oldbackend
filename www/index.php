<?php
if ( !defined('tmp_mc_path') )
{
	define('tmp_mc_path', dirname(__FILE__));
}

include_once tmp_mc_path . '/config.php';

if ( !isset($GLOBALS['cmd']) )
{
	$GLOBALS['cmd'] = @preg_replace("/[^a-z_]/i", $_GET['cmd']);
}

$GLOBALS['cmd'] = strtolower($GLOBALS['cmd']);

if ( $GLOBALS['cmd'] )
{
	include_once tmp_mc_path . '/utils/' . str_replace('_', '-', $GLOBALS['cmd']) . '.php';
}
elseif ( isset($_SESSION['user_id']) )
{
	include tmp_mc_path . '/user.php';
}
else
{
	include tmp_mc_path . '/login.php';
}
?>