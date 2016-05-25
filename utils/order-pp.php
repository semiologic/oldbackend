<?php
require_once tmp_mc_path . '/utils/session.php';


#
# validate_order()
#

function validate_order()
{
	/*
	// debug
	$title = 'PP Notification Data';

	$message = '';

	foreach ( $_POST as $key => $val )
	{
		$message .= $key . ': ' . $val . "\n";
	}

	send_email('support@semiologic.com', $title, $message, 'support@semiologic.com');
	*/

	if ( $_SERVER['HTTP_HOST'] != 'localhost' )
	{
		if ( empty($_POST) || !validate_pp() )
		{
			return false;
		}
	}

	if ( $_SERVER['HTTP_HOST'] == 'localhost' )
	{
		$_POST['item_number'] = $_REQUEST['transaction'];
	}

	$res = db_query("
			SELECT	*
			FROM	orders
			WHERE	order_key = '" . db_escape($_POST['item_number']) . "'
			;"
		);

	$order = db_get_row($res);

	if ( !$order )
	{
		return false;
	}

	if ( $_SERVER['HTTP_HOST'] == 'localhost' )
	{
		$_POST['business'] = pp_address;
		$_POST['mc_currency'] = 'USD';
		$_POST['mc_gross'] = $order['order_amount'];
		$_POST['payment_status'] = 'completed';
		$_POST['pending_reason'] = 'echeck';
		$_POST['txn_id'] = md5(time() + rand());
	}

	$is_valid = $_POST['business'] == pp_address
			&& $_POST['mc_currency'] == 'USD'
			&& $_POST['mc_gross']
			&& $_POST['mc_gross'] == $order['order_amount'];

	if ( $is_valid )
	{
		update_user_data($order['user_id']);

		switch ( strtolower($_POST['payment_status']) )
		{
			case 'completed':
			case 'cleared':
			case 'canceled-reversal':
			case 'cancelled-reversal':
				db_query("
						UPDATE	orders
						SET		order_status = 'cleared',
								transaction_id = '" . db_escape($_POST['txn_id']) . "'
						WHERE	order_key = '" . db_escape($_POST['item_number']) . "'
						;"
					);
				send_order_emails($order['order_id'], $order['order_status']);
				return true;
				break;

			case 'pending':
				if ( $_POST['pending_reason'] == 'echeck' )
				{
					db_query("
							UPDATE	orders
							SET		order_status = 'cleared',
									transaction_id = '" . db_escape($_POST['txn_id']) . "'
							WHERE	order_key = '" . db_escape($_POST['item_number']) . "'
							;"
						);

					send_order_emails($order['order_id'], $order['order_status']);
					return true;
				}
				else
				{
					db_query("
							UPDATE	orders
							SET		order_status = '" . db_escape($_POST['pending_reason']) . "',
									transaction_id = '" . db_escape($_POST['txn_id']) . "'
							WHERE	order_key = '" . db_escape($_POST['item_number']) . "'
							;"
						);

					send_order_emails($order['order_id'], $order['order_status']);
					return false;
				}
				break;

			default:
				db_query("
						UPDATE	orders
						SET		order_status = '" . db_escape($_POST['payment_status']) . "',
								transaction_id = '" . db_escape($_POST['txn_id']) . "'
						WHERE	order_key = '" . db_escape($_POST['item_number']) . "'
						;"
					);

				send_order_emails($order['order_id'], $order['order_status']);
				return false;
				break;
		}
	}
	else
	{
		db_query("
				UPDATE	orders
				SET		order_status = '" . db_escape($_POST['payment_status']) . "',
						transaction_id = '" . db_escape($_POST['txn_id']) . "'
				WHERE	order_key = '" . db_escape($_POST['item_number']) . "'
				;"
			);

		send_order_emails($order['order_id'], $order['order_status']);
		return false;
		break;
	}
} # validate_order()


#
# update_user_data()
#

function update_user_data($user_id)
{
	if ( $_SERVER['HTTP_HOST'] != 'localhost' )
	{
		db_query("
				UPDATE	users
				SET		user_name = '" . db_escape($_POST['first_name'] . ' ' . $_POST['last_name']) . "',
						user_phone = '" . db_escape($_POST['contact_phone']) . "',
						user_email = '" . db_escape($_POST['payer_email']) . "',
						user_paypal = '" . db_escape($_POST['payer_email']) . "'
				WHERE	user_id = '" . db_escape($user_id) . "'
				AND		user_name = ''
				;"
			);
	}
} # update_user_data()


#
# send_order_emails()
#

function send_order_emails($order_id, $old_status)
{
	$res = db_query("
			SELECT	*
			FROM	orders
			WHERE	order_id = '" . db_escape($order_id) . "'
			;"
		);

	$order = db_get_row($res);

	#$old_status = 'pending';
	#echo '<pre>';
	#var_dump($order);
	#echo '</pre>';

	if ( $order['order_status'] != $old_status )
	{
		if ( $order['aff_id'] )
		{
			$res = db_query("
					SELECT	*
					FROM	users
					WHERE	user_id = '" . db_escape($order['aff_id']) . "'
					;"
				);

			$aff = db_get_row($res);
		}
		else
		{
			$aff = null;
		}

		if ( $order['tier_id'] )
		{
			$res = db_query("
					SELECT	*
					FROM	users
					WHERE	user_id = '" . db_escape($order['tier_id']) . "'
					;"
				);

			$tier = db_get_row($res);
		}
		else
		{
			$tier = null;
		}

		$res = db_query("
				SELECT	*
				FROM	users
				WHERE	user_id = '" . db_escape($order['user_id']) . "'
				;"
			);

		$customer = db_get_row($res);

		#echo '<pre>';
		#var_dump($referrer, $customer);
		#echo '</pre>';

		switch ( $order['order_status'] )
		{
		case 'cleared':
			// notify referrers
			if ( $aff && $order['aff_commission'] )
			{
				$title = 'You have generated a sale on semiologic.com!';

				$message = $title . "\n\n"
					. 'Transaction: ' . $order['order_key'] . "\n"
					. 'Date: ' . date('M jS, Y @ H:i', strtotime($order['order_date'])) . "\n"
					. 'Customer: ' . $customer['user_name'] . "\n"
					. 'Amount: ' . $order['order_amount'] . "\n"
					. 'Commission: ' . $order['aff_commission'] . "\n"
					. "\n"
					. 'To access your campaign stats:' . "\n"
					. "\n"
					. 'http://oldbackend.semiologic.com/stats.php?user_key=' . $aff['user_key'] . "\n"
					. "\n"
					. 'Thank you for your continuing support to semiologic.com!' . "\n"
					. email_sig;

				send_email($aff['user_email'], $title, $message, 'sales@semiologic.com');
			}

			if ( $tier && $order['tier_commission'] )
			{
				$title = 'You have generated a sale on semiologic.com!';

				$message = $title . "\n\n"
					. 'Transaction: ' . $order['order_key'] . "\n"
					. 'Date: ' . date('M jS, Y @ H:i', strtotime($order['order_date'])) . "\n"
					. 'Customer: ' . $customer['user_name'] . "\n"
					. 'Amount: ' . $order['order_amount'] . "\n"
					. 'Commission: ' . $order['tier_commission'] . "\n"
					. "\n"
					. 'To access your campaign stats:' . "\n"
					. "\n"
					. 'http://oldbackend.semiologic.com/stats.php?user_key=' . $tier['user_key'] . "\n"
					. "\n"
					. 'Thank you for your continuing support to semiologic.com!' . "\n"
					. email_sig;

				send_email($tier['user_email'], $title, $message, 'sales@semiologic.com');
			}

			// notify customer
			$title = 'Your order on semiologic.com!';

			$message = 'Thank you for your business on semiologic.com!' . "\n\n"
				. 'Please find the Semiologic Pro members area at the following location:' . "\n"
				. "\n"
				. 'http://www.semiologic.com/members/sem-pro/' . "\n"
				. "\n"
				. 'To access your profile details:' . "\n"
				. "\n"
				. 'http://oldbackend.semiologic.com?user_key=' . $customer['user_key'] . "\n"
				. "\n"
				. 'To your online success!' . "\n"
				. email_sig;

			send_email($customer['user_email'], $title, $message, 'support@semiologic.com');

			// notify sales
			$title = 'New order on semiologic.com!';

			$message = $title . "\n\n"
				. 'Transaction: ' . $order['order_key'] . "\n"
				. 'PP Transaction: ' . $order['transaction_id'] . "\n"
				. 'Date: ' . date('M jS, Y @ H:i', strtotime($order['order_date'])) . "\n"
				. 'Customer: ' . $customer['user_name'] . "\n"
				. 'Phone: ' . $customer['user_phone'] . "\n"
				. 'Email: ' . $customer['user_email'] . "\n"
				. 'Amount: ' . $order['order_amount'] . "\n"
				. ( $aff
					? ( "\n"
						. 'Affiliate: ' . $aff['user_name'] . "\n"
						. 'Phone: ' . $aff['user_phone'] . "\n"
						. 'Email: ' . $aff['user_email'] . "\n"
						. 'Commission: ' . $order['aff_commission'] . "\n\n"
						)
					: ''
					)
				. ( $tier
					? ( "\n"
						. 'Tier: ' . $tier['user_name'] . "\n"
						. 'Phone: ' . $tier['user_phone'] . "\n"
						. 'Email: ' . $tier['user_email'] . "\n"
						. 'Commission: ' . $order['tier_commission'] . "\n\n"
						)
					: ''
					);

			send_email(pp_address, $title, $message, $customer['user_email']);
			break;

		default:
			// notify affiliate
			if ( $aff )
			{
				$title = 'The sale you generated on semiologic.com has been reversed!';

				$message = $title . "\n\n"
					. 'Transaction: ' . $order['order_key'] . "\n"
					. 'Date: ' . date('M jS, Y @ H:i', strtotime($order['order_date'])) . "\n"
					. 'Customer: ' . $customer['user_name'] . "\n"
					. 'Amount: ' . $order['order_amount'] . "\n"
					. 'Commission: ' . $order['aff_commission'] . "\n" . "\n"
					. "\n"
					. 'To access your affiliate stats:' . "\n"
					. "\n"
					. 'http://oldbackend.semiologic.com/stats.php?user_key=' . $aff['user_key'] . "\n"
					. "\n"
					. 'Thank you for your continuing support to semiologic.com!' . "\n"
					. email_sig;

				send_email($aff['user_email'], $title, $message, 'sales@semiologic.com');
			}

			if ( $tier )
			{
				$title = 'The sale you generated on semiologic.com has been reversed!';

				$message = $title . "\n\n"
					. 'Transaction: ' . $order['order_key'] . "\n"
					. 'Date: ' . date('M jS, Y @ H:i', strtotime($order['order_date'])) . "\n"
					. 'Customer: ' . $customer['user_name'] . "\n"
					. 'Amount: ' . $order['order_amount'] . "\n"
					. 'Commission: ' . $order['aff_commission'] . "\n" . "\n"
					. "\n"
					. 'To access your affiliate stats:' . "\n"
					. "\n"
					. 'http://oldbackend.semiologic.com/stats.php?user_key=' . $tier['user_key'] . "\n"
					. "\n"
					. 'Thank you for your continuing support to semiologic.com!' . "\n"
					. email_sig;

				send_email($tier['user_email'], $title, $message, 'sales@semiologic.com');
			}

			// notify sales
			$title = 'Order on semiologic.com in need of investigation!';

			$message = $title . "\n\n"
				. 'Transaction: ' . $order['order_key'] . "\n"
				. 'New Status: ' . $order['order_status'] . "\n"
				. 'PP Transaction: ' . $order['transaction_id'] . "\n"
				. 'Date: ' . date('M jS, Y @ H:i', strtotime($order['order_date'])) . "\n"
				. 'Amount: ' . $order['order_amount'] . "\n"
				. 'Customer: ' . $customer['user_name'] . "\n"
				. 'Phone: ' . $customer['user_phone'] . "\n"
				. 'Email: ' . $customer['user_email'] . "\n"
				. ( $aff
					? ( "\n"
						. 'Affiliate: ' . $aff['user_name'] . "\n"
						. 'Phone: ' . $aff['user_phone'] . "\n"
						. 'Email: ' . $aff['user_email'] . "\n"
						. 'Commission: ' . $order['aff_commission'] . "\n\n"
						)
					: ''
					)
				. ( $tier
					? ( "\n"
						. 'Tier: ' . $tier['user_name'] . "\n"
						. 'Phone: ' . $tier['user_phone'] . "\n"
						. 'Email: ' . $tier['user_email'] . "\n"
						. 'Commission: ' . $order['tier_commission'] . "\n\n"
						)
					: ''
					);

			send_email(pp_address, $title, $message, $customer['user_email']);
		}
	}
} # send_order_emails()


#
# display_order_details()
#

function display_order_details()
{
	@session_start();

	include_once tmp_mc_path . '/header.php';

	display_messages();
?>

<h1>Semiologic Pro</h1>

<p>Thank you for your business!</p>

<p>Please find the Semiologic Pro members area at the following location:</p>

<ul>
	<li><a href="http://www.semiologic.com/members/sem-pro/" target="_blank">http://www.semiologic.com/members/sem-pro/</a></li>
</ul>

<p>You will receive the above url by email. As emails occasionally get caught by spam filters, be sure to check in your junk mail folder if you do not receive them.</p>

<p>Mike Koepke<br />
support at semiologic dot com</p>

<?php

	$res = db_query("
			SELECT	*
			FROM	users
			INNER JOIN orders
			ON		orders.aff_id = users.user_id
			WHERE	order_key = '" . db_escape($_POST['item_number']) . "'
			;"
		);

	$aff = db_get_row($res);

	if ( $aff )
	{
		$res = db_query("
				SELECT	*
				FROM	orders
				WHERE	order_key = '" . db_escape($_POST['item_number']) . "'
				;"
			);

		$order = db_get_row($res);

		if ( $aff['google_conversion_id'] )
		{
?>
<!-- Google Code for purchase Conversion Page -->
<script language="JavaScript" type="text/javascript">
<!--
var google_conversion_id = <?php echo $aff['google_conversion_id']; ?>;
var google_conversion_language = "en_US";
var google_conversion_format = "1";
var google_conversion_color = "FFFFFF";
var google_conversion_value = <?php echo $order['aff_commission']; ?>;
var google_conversion_label = "purchase";
//-->
</script>
<script language="JavaScript" src="http://www.googleadservices.com/pagead/conversion.js"></script>
<noscript>
<img height="1" width="1" border="0" src="http://www.googleadservices.com/pagead/conversion/<?php echo $aff['google_conversion_id']; ?>/imp.gif?value=<?php echo $order['aff_commission']; ?>&label=purchase&script=0 ">
</noscript>
<?php
		}

		if ( $aff['microsoft_adcenterconversion_domainid'] )
		{
?>
<SCRIPT>
microsoft_adcenterconversion_domainid = <?php echo $aff['microsoft_adcenterconversion_domainid']; ?>;
microsoft_adcenterconversion_cp = 5050;
</script>
<SCRIPT SRC="https://0.r.msn.com/scripts/microsoft_adcenterconversion.js"></SCRIPT>
<NOSCRIPT>
<IMG width="1" height="1" SRC=" https://<?php echo $aff['microsoft_adcenterconversion_domainid']; ?>.r.msn.com/?type=1&cp=1" />
</NOSCRIPT>
<?php
		}
	}


	include_once tmp_mc_path . '/footer.php';
} # display_order_details()


#
# display_order_error()
#

function display_order_error()
{
	@session_start();

	include_once tmp_mc_path . '/header.php';

	display_messages();
?>

<h1>Semiologic Pro</h1>

<p>A network lag is apparently preventing PayPal from instantly validating your transaction. It will usually resolve by itself in a few minutes, giving you access to members.semiologic.com using the user details you signed up with.</p>

<p>Please try again in a few minutes. If it's not working within the hour, please email support at semiologic dot com. You can alternatively reach me on my <a href="http://www.semiologic.com/about/">cell phone</a> (please mind the time zone if you do).</p>

<p>Mike Koepke<br />
support at semiologic dot com</p>

<?php
	include_once tmp_mc_path . '/footer.php';
} # display_order_error()
?>
