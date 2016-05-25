<?php
$GLOBALS['cmd'] = 'payments';

include dirname(__FILE__) . '/index.php';

if ( !$_SESSION['is_admin'] )
{
	display_payments();
}
else
{
	admin_payments();
}
?>