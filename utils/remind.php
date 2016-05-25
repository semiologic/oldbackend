<?php
require_once tmp_mc_path . '/utils/session.php';


#
# process_remind()
#

function process_remind()
{
	$res = db_query("
			SELECT get_user_key('" . db_escape($_POST['email']) . "')
		");

	if ( $user_key = db_get_var($res) )
	{
		$title = 'Semiologic Password Reminder';

		$message = 'You\'ve requested a password reminder.' . "\n"
		. "\n"
		. 'To log into the members area and change your password, please visit:' . "\n"
		. "\n"
		. 'http://oldbackend.semiologic.com?user_key=' . $user_key . "\n"
		. email_sig;

		send_email($_POST['email'], $title, $message, 'support@semiologic.com');

		add_message("You've received instructions to reset your password.");
	}
	else
	{
		add_message("User Not Found!");
	}
} # process_remind()


#
# do_remind()
#

function do_remind()
{
	if ( isset($_SESSION['user_id']) )
	{
		do_redirect('user.php');
	}

	if ( !empty($_POST) )
	{
		process_remind();
	}

	include_once tmp_mc_path . '/header.php';

	display_messages();

	echo '<form method="post" action="remind.php">' . "\n";

	echo '<p class="field">Email:<br />' . "\n"
		. '<input type="text"'
			. ' name="email"'
			. ' value=""'
			. ' />' . "\n";

	echo '<p class="submit">'
		. '<button type="submit">Send Password</button>'
		. '</p>' . "\n";

	echo '</form>' . "\n";

	include_once tmp_mc_path . '/footer.php';
} # do_remind()
?>