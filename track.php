<?php
$GLOBALS['cmd'] = 'track';

include dirname(__FILE__) . '/index.php';

if ( strpos(tmp_mc_path . '/track.php', ltrim($_SERVER['SCRIPT_NAME'], '/')) !== false )
{
	track_ref();
}
else
{
	redirect_track();
}
?>