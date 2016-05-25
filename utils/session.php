<?php
require_once tmp_mc_path . '/utils/db.php';
require_once tmp_mc_path . '/utils/utils.php';


#
# check_user_key()
#

function check_user()
{
	@session_start();

	if ( isset($_GET['user_key']) )
	{
		$res = db_query("
			SELECT	user_id,
					is_admin,
					aff_is_gold,
					aff_is_reseller
			FROM	users
			WHERE	user_key='" . db_escape($_GET['user_key']) . "'
			");

		if ( $row = db_get_row($res) )
		{
			$_SESSION['user_id'] = $row['user_id'];
			$_SESSION['is_admin'] = $row['is_admin'] && ( $row['is_admin'] != 'f' );
			$_SESSION['aff_is_gold'] = $row['aff_is_gold'] && ( $row['aff_is_gold'] != 'f' );
			$_SESSION['aff_is_reseller'] = $row['aff_is_reseller'] && ( $row['aff_is_reseller'] != 'f' );
		}
		else
		{
			unset($_SESSION['user_id']);
			require_user();
		}
	}
	elseif ( isset($_SESSION['user_id']) )
	{
		$res = db_query("
			SELECT	user_id,
					is_admin,
					aff_is_gold,
					aff_is_reseller
			FROM	users
			WHERE	user_id='" . db_escape($_SESSION['user_id']) . "'
			");

		if ( $row = db_get_row($res) )
		{
			$_SESSION['user_id'] = $row['user_id'];
			$_SESSION['is_admin'] = $row['is_admin'] && ( $row['is_admin'] != 'f' );
			$_SESSION['aff_is_gold'] = $row['aff_is_gold'] && ( $row['aff_is_gold'] != 'f' );
			$_SESSION['aff_is_reseller'] = $row['aff_is_reseller'] && ( $row['aff_is_reseller'] != 'f' );
		}
		else
		{
			unset($_SESSION['user_id']);
			require_user();
		}
	}
} # check_user()


#
# require_user()
#

function require_user()
{
	if ( !isset($_SESSION['user_id']) )
	{
		unset($_SESSION['user_id']);
		unset($_SESSION['is_admin']);
		unset($_SESSION['aff_is_gold']);
		unset($_SESSION['aff_is_reseller']);

		$GLOBALS['cmd'] = 'login';
		$_SESSION['redirect'] = $_SERVER['SCRIPT_NAME'];

		include tmp_mc_path . '/login.php';
	}
} # require_user()


#
# require_admin()
#

function require_admin()
{
	if ( !isset($_SESSION['is_admin']) || !$_SESSION['is_admin'] )
	{
		die('Access Denied');
	}
}

check_user();
?>