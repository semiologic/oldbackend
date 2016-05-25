<?php
die('Sold Out');

$GLOBALS['cmd'] = 'order';

include dirname(__FILE__) . '/index.php';

process_order();
?>
