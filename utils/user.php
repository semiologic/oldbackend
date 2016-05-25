<?php
require_once tmp_mc_path . '/utils/session.php';
require_user();


#
# update_user()
#

function update_user()
{
	#echo '<pre>';
	#var_dump($_POST);
	#echo '</pre>';
	#die();

	add_message('Profile Saved.');

	$_POST['user_pass'] = '';
	$_POST['user_pass1'] = trim($_POST['user_pass1']);
	$_POST['user_pass2'] = trim($_POST['user_pass2']);

	if ( $_POST['user_pass1'] || $_POST['user_pass2'] )
	{
		if ( $_POST['user_pass1'] == $_POST['user_pass2'] )
		{
			add_message('Password Changed.');
			$_POST['user_pass'] = $_POST['user_pass1'];
		}
		else
		{
			add_message('Password Not Changed (Mismatch).');
		}
	}

	$res = db_query("
		UPDATE	users
		SET		user_name = '" . db_escape($_POST['user_name']) . "',
				user_phone = '" . db_escape($_POST['user_phone']) . "',
				user_email = '" . db_escape($_POST['user_email']) . "',
				user_paypal = '" . db_escape($_POST['user_paypal']) . "',
				user_pass = CASE
					WHEN '" . db_escape($_POST['user_pass']) . "' <> ''
					THEN
						md5('" . db_escape($_POST['user_pass']) . "')
					ELSE
						user_pass
					END
		WHERE	user_id = '" . db_escape($_SESSION['user_id']) . "'
		");

	do_redirect('user.php');
} # update_user()


#
# display_user()
#

function display_user()
{
	if ( !empty($_POST) )
	{
		update_user();
	}

	include_once tmp_mc_path . '/header.php';

	display_messages();

	$res = db_query("
		SELECT	*
		FROM	users
		WHERE	user_id = '" . db_escape($_SESSION['user_id']) . "'
		");

	$row = db_get_row($res);

	echo '<form method="post" action="user.php">' . "\n";

	echo '<h1>Profile</h1>';


	echo '<h2>Semiologic API key</h2>';

	echo '<p>Your Semiologic API key is unique to your account. <strong>Do not share it</strong>. Knowing it is equivalent to knowing your email and password.</p>';

	echo '<p class="field">'
		. '<input type="text"'
			. ' readonly="readonly"'
			. ' value="' . htmlspecialchars($row['user_key'], ENT_QUOTES) . '"'
			. ' />'
		. '</p>' . "\n";


	echo '<h2>Contact Details</h2>';

	echo '<p>Your contact details allow the semiologic.com team to contact you when necessary, <em>e.g.</em> to deliver support.</p>';

	echo '<p class="field">'
		. 'Name:<br />' . "\n"
		. '<input type="text"'
			. ' name="user_name"'
			. ' value="' . htmlspecialchars($row['user_name'], ENT_QUOTES) . '"'
			. ' />'
		. '</p>' . "\n";

	echo '<p class="field">'
		. 'Phone:<br />' . "\n"
		. '<input type="text"'
			. ' name="user_phone"'
			. ' value="' . htmlspecialchars($row['user_phone'], ENT_QUOTES) . '"'
			. ' />'
		. '</p>' . "\n";

	echo '<p class="field">'
		. 'Email:<br />' . "\n"
		. '<input type="text"'
			. ' name="user_email"'
			. ' value="' . htmlspecialchars($row['user_email'], ENT_QUOTES) . '"'
			. ' />'
		. '</p>' . "\n";

	echo '<p class="submit">'
		. '<button type="submit">Save</button>'
		. '</p>' . "\n";


	echo '<h2>Change Password</h2>';

	echo '<p>To change your password, enter it twice below.</p>';

	echo '<p class="field">'
		. '<input type="password"'
			. ' name="user_pass1"'
			. ' value=""'
			. ' />'
		. '<input type="password"'
			. ' name="user_pass2"'
			. ' value=""'
			. ' />'
			. '<br />' . "\n"
		. '</p>' . "\n";

	echo '<p class="submit">'
		. '<button type="submit">Save</button>'
		. '</p>' . "\n";


	echo '<h2>Affiliate Program</h2>';

	echo '<p>To promote Semiologic Pro, enter a valid <a href="http://www.paypal.com">paypal address</a> to receive payments, and create an <a href="campaigns.php">affiliate campaign</a>. <a href="http://www.semiologic.com/partners/">More details</a>.</p>';

	echo '<p class="field">'
		. 'Paypal Address:<br />' . "\n"
		. '<input type="text"'
			. ' name="user_paypal"'
			. ' value="' . htmlspecialchars($row['user_paypal'], ENT_QUOTES) . '"'
			. ' />'
		. '</p>' . "\n";

	echo '<p class="submit">'
		. '<button type="submit">Save</button>'
		. '</p>' . "\n";

	echo '</form>' . "\n";

	include_once tmp_mc_path . '/footer.php';
} # display_user()
?>