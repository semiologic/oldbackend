<?php
require_once tmp_mc_path . '/utils/session.php';
require_once tmp_mc_path . '/utils/markdown.php';


#
# do_checkout()
#

function do_checkout($order_id)
{
	$res = db_query("
		SELECT	*
		FROM	orders
		WHERE	order_id = '" . db_escape($order_id) . "'
		");

	if ( ( $row = db_get_row($res) ) )
	{
		$location = 'https://' . pp_url . '/cgi-bin/webscr'
			. '?cmd=_xclick'
			. '&business=' . urlencode(pp_address)
			. '&amount=' . $row['order_amount']
			. '&currency_code=USD'
			. '&item_name=' . urlencode('Order on semiologic.com')
			. '&item_number=' . $row['order_key']
			. '&no_shipping=1'
			. '&no_note=1'
			. '&notify_url=' . urlencode('http://oldbackend.semiologic.com/order-pp.php')
			. '&return=' . urlencode('http://oldbackend.semiologic.com/order-pp.php')
			. '&charset=UTF-8';

		#var_dump($location);
		#var_dump($amount);

		if ( $_SERVER['HTTP_HOST'] != 'localhost' )
		{
			do_redirect($location);
		}
		else
		{
			do_redirect('order-pp.php?transaction=' . $row['order_key']);
		}
	}
} # do_checkout()


#
# check_order()
#

function check_order()
{
	$valid = true;

	#echo '<pre>';
	#var_dump($_POST);
	#echo '</pre>';
	#die;

	if ( isset($_POST['order_key']) )
	{
		if ( !isset($_POST['product']) )
		{
			add_message('Please choose a product below.');
			$valid = false;
		}

		if ( !isset($_SESSION['user_id']) )
		{
			foreach ( array ('name', 'phone', 'email', 'pass1', 'pass2') as $var )
			{
				if ( !isset($_POST['user_' . $var]) )
				{
					return false;
				}

				$_POST['user_' . $var] = trim($_POST['user_' . $var]);
			}

			if ( !$_POST['user_name'] || !$_POST['user_phone'] || !$_POST['user_email']
				|| !preg_match("/^[a-z0-9\.\-_]+@[a-z0-9\.\-_]+$/i", $_POST['user_email'])
				)
			{
				add_message('Please enter your contact details.');
				$valid = false;
			}

			if ( !$_POST['user_pass1'] )
			{
				add_message('Please choose and enter a password.');
				$valid = false;
			}
			elseif ( $_POST['user_pass1'] != $_POST['user_pass2'] )
			{
				add_message('Password mismatch. Please re-enter your password.');
				$valid = false;
			}

			# one more test for db consistency
			if ( $valid )
			{
				$res = db_query("
					SELECT	check_user_email(
						'" . db_escape($_POST['user_email']) . "',
						'" . db_escape($_POST['order_key']) . "'
						);
					");

				$bool = db_get_var($res);

				$bool = $bool && ( $bool != 'f' );

				if ( !$bool )
				{
					add_message('A user with this email exists already.<br />'
						. 'Please <a href="login.php">login</a> before placing your order.');
					$valid = false;
				}
			}
		}
	}
	else
	{
		$_POST['order_key'] = md5(time() + rand());

		if ( !isset($_POST['product']) && isset($_GET['product']) )
		{
			$_POST['product'] = array($_GET['product']);

			$valid = isset($_SESSION['user_id']);
		}
		else
		{
			$valid = false;
		}
	}

	# checkout if valid
	if ( $valid )
	{
		$product_keys = '';

		foreach ( $_POST['product'] as $product_key )
		{
			$product_keys .= ( $product_keys ? ', ' : '' )
				. $product_key;
		}

		if ( !isset($_SESSION['user_id']) )
		{
			$res = db_query("
				SELECT	next_order(
					'" . db_escape($_POST['order_key']) . "',
					'" . db_escape($_POST['user_name']) . "',
					'" . db_escape($_POST['user_phone']) . "',
					'" . db_escape($_POST['user_email']) . "',
					'" . db_escape($_POST['user_pass1']) . "',
					" . ( $_SESSION['campaign_key']
						? ( "'" . db_escape($_SESSION['campaign_key']) . "'" )
						: "null"
						)
					. ",
					'" . db_escape($product_keys) . "'
					);
				");

			$order_id = db_get_var($res);

			do_checkout($order_id);
		}
		else
		{
			$res = db_query("
				SELECT	next_order(
					'" . db_escape($_POST['order_key']) . "',
					'" . db_escape($_SESSION['user_id']) . "',
					" . ( $_SESSION['campaign_key']
						? ( "'" . db_escape($_SESSION['campaign_key']) . "'" )
						: "null"
						)
					. ",
					'" . db_escape($product_keys) . "'
					);
				");

			$order_id = db_get_var($res);

			do_checkout($order_id);
		}
	}

	return $valid;
} # check_order()


#
# process_order()
#

function process_order()
{
	@session_start();

	if ( isset($_SESSION['user_id']) )
	{
		require_once tmp_mc_path . '/utils/session.php';

		$user_id = $_SESSION['user_id'];
	}
	else
	{
		$user_id = null;
	}

	if ( isset($_GET['coupon']) )
	{
		$res = db_query("
			SELECT	campaigns.*
			FROM	campaigns
			INNER JOIN user2campaign
			ON		campaigns.campaign_id = user2campaign.campaign_id
			WHERE	campaigns.campaign_key = str2key('" . db_escape($_GET['coupon']) . "')
			AND		"
			. ( $user_id
				? ( "user_id = '" . db_escape($user_id) . "'" )
				: "user_id IS NULL"
				)
			. "
			");

		if ( ( $row = db_get_row($res) )
			&& $row['coupon_is_active']
			&& $row['coupon_is_active'] != 'f'
			)
		{
			$_SESSION['campaign_key'] = $row['campaign_key'];
		}
	}

	if ( !isset($_SESSION['campaign_key']) )
	{
		if ( isset($_COOKIE['ref_id']) )
		{
			$_SESSION['campaign_key'] = $_COOKIE['ref_id'];
		}
		else
		{
			$_SESSION['campaign_key'] = null;
		}
	}

	$res = db_query("
		SELECT	*
		FROM	user2campaign
		WHERE	"
		. ( $_SESSION['campaign_key']
			? ( "campaign_key = str2key('" . db_escape($_SESSION['campaign_key']) . "')" )
			: "campaign_key IS NULL"
			)
		. "
		AND		"
		. ( $user_id
			? ( "user_id = '" . db_escape($user_id) . "'" )
			: "user_id IS NULL"
			)
		. "
		");

	if ( !db_get_row($res) )
	{
		$_SESSION['campaign_key'] = null;
	}

	check_order();

	include_once tmp_mc_path . '/header.php';

	display_messages();

	echo '<form method="post" action="order.php">';

	echo '<input type="hidden" name="order_key"'
		. ' value="' . $_POST['order_key'] . '"'
		. ' />';

	echo '<h1>New Order</h1>';

	if ( !isset($_SESSION['user_id']) )
	{
		echo '<h2>Contact Details</h2>';

		echo '<p>Your contact details allow the semiologic.com team to contact you when necessary, <em>e.g.</em> to deliver support.</p>';

		echo '<p class="field">'
			. 'Name:<br />' . "\n"
			. '<input type="text"'
				. ' name="user_name"'
				. ' value="' . ( isset($_POST['user_name']) ? htmlspecialchars($_POST['user_name'], ENT_QUOTES) : '' ) . '"'
				. ' />'
			. '</p>' . "\n";

		echo '<p class="field">'
			. 'Phone:<br />' . "\n"
			. '<input type="text"'
				. ' name="user_phone"'
				. ' value="' . ( isset($_POST['user_phone']) ? htmlspecialchars($_POST['user_phone'], ENT_QUOTES) : '' ) . '"'
				. ' />'
			. '</p>' . "\n";

		echo '<p class="field">'
			. 'Email:<br />' . "\n"
			. '<input type="text"'
				. ' name="user_email"'
				. ' value="' . ( isset($_POST['user_email']) ? htmlspecialchars($_POST['user_email'], ENT_QUOTES) : '' ) . '"'
				. ' />'
			. '</p>' . "\n";

		echo '<h2>Password</h2>';

		echo '<p>Please choose a password and enter it twice below.</p>';

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
	}

	echo '<h2>Product</h2>';

	echo '<p>Please choose one or more products below.</p>';

	if ( !isset($_POST['product']) )
	{
		$_POST['product'] = array();
	}

	$res = db_query("
			SELECT	*
			FROM	pricelist
			WHERE	"
			. ( $user_id
				? ( "user_id = '" . db_escape($user_id) . "'" )
				: "user_id IS NULL"
				)
			. "
			AND		"
			. ( isset($_SESSION['campaign_key'])
				? ( "campaign_key = '" . db_escape($_SESSION['campaign_key']) . "'" )
				: "campaign_key IS NULL"
				)
			. "
			ORDER BY product_name
		");

	echo '<table width="510">';

	while ( $row = db_get_row($res) )
	{
		echo '<tr align="center">'
			. '<td align="left">'
			. '<label for="product[' . $row['product_key'] . ']">'
			. '<input type="checkbox"'
				. ' name="product[]" id="product[' . $row['product_key'] . ']"'
				. ' value="' . $row['product_key'] . '"'
				. ( in_array($row['product_key'], (array) $_POST['product']) || ( db_num_rows($res) == 1 )
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '&nbsp;'
			. '<strong>' . $row['product_name'] . '</strong>'
			. ( $row['product_discount']
				? ( ' ('
					. $row['product_discount']
					. '% off)'
					)
				: ''
				)
			. ( $row['product_desc']
				? ( markdown($row['product_desc'])
					)
				: ''
				)
			. '</label>'
			. '</td>'
			. '<td align="right">'
			. '<label for="product[' . $row['product_key'] . ']">'
			. number_format($row['product_price'] * ( 100 - $row['product_discount'] ) / 100, 2)
			. '</label>'
			. '</td>'
			. '</tr>';
	}

	echo '</table>';

	echo '<p class="submit">'
		. '<button type="submit">Checkout</button>'
		. '</p>' . "\n";

	echo '</form>';

	include_once tmp_mc_path . '/footer.php';
} # process_order()

?>
