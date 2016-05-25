<?php
require_once tmp_mc_path . '/utils/session.php';


#
# do_logout()
#

function do_logout()
{
	unset($_SESSION['user_id']);
	unset($_SESSION['is_admin']);
	unset($_SESSION['aff_is_gold']);
	unset($_SESSION['aff_is_reseller']);

	do_redirect('login.php');
} # do_logout()
?>