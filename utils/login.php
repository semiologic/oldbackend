<?php
require_once tmp_mc_path . '/utils/session.php';

#
# process_login()
#

function process_login()
{
	$res = db_query("
			SELECT get_user_id('" . db_escape($_POST['email']) . "', '" . db_escape($_POST['password']) . "')
		");

	if ( $user_id = db_get_var($res) )
	{
		$_SESSION['user_id'] = $user_id;

		if ( isset($_SESSION['redirect']) )
		{
			$location = $_SESSION['redirect'];
			unset($_SESSION['redirect']);
		}
		else
		{
			$location = 'user.php';
		}

		do_redirect($location);
	}
	elseif ( $_SERVER['HTTP_HOST'] == 'localhost' )
	{
		$res = db_query("
				SELECT	substring(user_key from 1 for 8)
				FROM	users
				WHERE	user_email = '" . db_escape($_POST['email']) . "'
			");

		add_message('Login Failed!');
		$pass = db_get_var($res);
		add_message($pass);

	}
	else
	{
		add_message('Login Failed!');
	}
} # process_login()


#
# do_login()
#

function do_login()
{
	if ( isset($_SESSION['user_id']) )
	{
		do_redirect('user.php');
	}

	if ( !empty($_POST) )
	{
		process_login();
	}

	include_once tmp_mc_path . '/header.php';

	echo '<div id="login">' . "\n"
		. '<div class="pad">' . "\n";

	display_messages();

	echo '<form method="post" action="login.php">' . "\n";

	echo '<p class="field">Email:<br />' . "\n"
		. '<input type="text"'
			. ' name="email"'
			. ' value="' . ( isset($_POST['email']) ? $_POST['email'] : '' ) . '"'
			. ' />' . "\n";

	echo '<p class="field">Password:<br />' . "\n"
		. '<input type="password"'
			. ' name="password"'
			. ' value=""'
			. ' />'
		. '</p>' . "\n";


	echo '<div style="float: left">'
		. '<a href="register.php">Register</a>'
		. ' - '
		. '<a href="remind.php">I lost my password</a>'
		. '</div>' . "\n";

	echo '<p class="submit">'
		. '<button type="submit">Login</button>'
		. '</p>' . "\n";

	echo '</form>' . "\n"
		. '</div>' . "\n"
		. '</div>' . "\n";

	include_once tmp_mc_path . '/footer.php';
} # do_login()
?>