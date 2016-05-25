<?php
require_once tmp_mc_path . '/utils/session.php';


#
# process_register()
#

function process_register()
{
	$res = db_query("
			SELECT get_user_key('" . db_escape($_POST['email']) . "')
		");

	if ( $user_key = db_get_var($res) )
	{
		add_message('This user exists already. <a href="remind.php">Retrieve your lost password</a>.');

		return;
	}

	if ( $_POST['password'] != $_POST['password2'] )
	{
		add_message('Password Mismatch. Please retype it.');
	}

	if ( $_POST['password'] != trim($_POST['password']) )
	{
		add_message('Your password contained leading or trailing spaces. Please retype it.');

		return;
	}

	if ( strlen($_POST['password']) < 4 )
	{
		add_message('Your password is too short. For security reasons, please use 4 or more characters.');

		return;
	}

	$sql = "
		INSERT INTO users ( user_name, user_phone, user_email, user_pass )
		VALUES ('" . db_escape($_POST['name']) . "', '" . db_escape($_POST['phone']) . "', '" . db_escape($_POST['email']) . "', md5('" . db_escape($_POST['password']) . "'))
		";

	db_query($sql);

	$title = 'Semiologic Registration';

	$message = 'Thank you for registering to the Semiologic members area.' . "\n"
	. "\n"
	. 'For future reference, you\'ve registered with the following details:' . "\n"
	. "\n"
	. 'Email: ' . $_POST['email'] . "\n"
	. 'Password: ' . $_POST['password'] . "\n"
	. "\n"
	. 'To log in, visit:' . "\n"
	. "\n"
	. 'http://oldbackend.semiologic.com' . "\n"
	. email_sig;

	send_email($_POST['email'], $title, $message, 'support@semiologic.com');

	$res = db_query("
		SELECT	user_id
		FROM	users
		WHERE	user_key = get_user_key('" . $_POST['email'] . "')
		");

	$user_id = db_get_var($res);

	$_SESSION['user_id'] = $user_id;

	do_redirect('user.php');
} # process_register()


#
# do_register()
#

function do_register()
{
	if ( isset($_SESSION['user_id']) )
	{
		do_redirect('user.php');
	}

	if ( !empty($_POST) )
	{
		process_register();
	}

	include_once tmp_mc_path . '/header.php';

	echo '<div id="register">' . "\n"
		. '<div class="pad">' . "\n";

	display_messages();

	echo '<form method="post" action="register.php">' . "\n";

	echo '<p class="field">Name:<br />' . "\n"
		. '<input type="text"'
			. ' name="name"'
			. ' value="' . ( isset($_POST['name']) ? $_POST['name'] : '' ) . '"'
			. ' />' . "\n";

	echo '<p class="field">Phone:<br />' . "\n"
		. '<input type="text"'
			. ' name="phone"'
			. ' value="' . ( isset($_POST['phone']) ? $_POST['phone'] : '' ) . '"'
			. ' />' . "\n";

	echo '<p class="field">Email:<br />' . "\n"
		. '<input type="text"'
			. ' name="email"'
			. ' value="' . ( isset($_POST['email']) ? $_POST['email'] : '' ) . '"'
			. ' />' . "\n";

	echo '<p class="field">Password (twice):<br />' . "\n"
		. '<input type="password"'
			. ' name="password"'
			. ' value=""'
			. ' />'
		. '<br />'
		. '<input type="password"'
			. ' name="password2"'
			. ' value=""'
			. ' />'
		. '</p>' . "\n";


	echo '<p class="submit">'
		. '<button type="submit">Register</button>'
		. '</p>' . "\n";

	echo '<p>'
		. 'Notice to earlier Semiologic customers: You are in the database already. The password reminder with the paypal address you\'ve used while placing your order is 95% likely to get you in.'
		. '</p>';

	echo '</form>' . "\n"
		. '</div>' . "\n"
		. '</div>' . "\n";

	include_once tmp_mc_path . '/footer.php';
} # do_register()
?>