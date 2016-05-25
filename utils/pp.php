<?php
require_once mc_path . '/utils/db.php';


#
# validate_pp()
#

function validate_pp($retry = 5)
{
	if ($retry < 0) {
		return false;
	} elseif ($retry < 5) {
		usleep(300000); # sleep 300ms
	}
	
	$paypal_url = pp_url;

	// read the post from PayPal system and add 'cmd'
	$req = 'cmd=_notify-validate';

	foreach ( $_POST as $key => $value )
	{
		$value = urlencode($value);
		$req .= "&$key=$value";
	}

	// post back to PayPal system to validate
	$header = "POST /cgi-bin/webscr HTTP/1.0\r\n"
			. "Content-Type: application/x-www-form-urlencoded\r\n"
			. "Content-Length: " . strlen($req) . "\r\n\r\n";
	$fp = fsockopen ($paypal_url, 80, $errno, $errstr, 30);

	if (!$fp)
	{
	// HTTP ERROR
	}
	else
	{
		fputs ($fp, $header . $req);

		while (!feof($fp))
		{
			$res = fgets ($fp, 1024);
			if ( strcmp ($res, "VERIFIED") == 0 )
			{
				// check the payment_status is Completed
				// check that txn_id has not been previously processed
				// check that receiver_email is your Primary PayPal email
				// check that payment_amount/payment_currency are correct
				// process payment

				fclose ($fp);

				return true;
			}
			elseif (strcmp ($res, "INVALID") == 0)
			{
				// log for manual investigation

				fclose ($fp);

				return validate_pp($retry - 1);
			}
		}
		fclose ($fp);
	}

	return validate_pp($retry - 1);
} # validate_pp()



#
# validate_order()
#

function validate_order()
{
	$validate_pp = validate_pp();

	if ( empty($_POST) || !$validate_pp )
	{
		return false;
	}

	$res = db_query("
			SELECT	*
			FROM	orders
			WHERE	order_id = '" . db_escape($_POST['item_number']) . "'
			;"
		);

	$order = db_get_row($res);

	if ( !$order )
	{
		return false;
	}

	if ( !( $_POST['business'] == 'finance@mesoconcepts.com'
			&& $_POST['mc_currency'] == 'USD'
			&& $_POST['mc_gross'] == $order['order_amount']
			)
		)
	{
		return false;
	}

	update_user_data($order['user_id']);

	switch ( strtolower($_POST['payment_status']) )
	{
		case 'completed':
		case 'canceled-reversal':
			db_query("
					UPDATE	orders
					SET		order_status = 'cleared',
							transaction_id = '" . db_escape($_POST['txn_id']) . "'
					WHERE	order_id = '" . db_escape($_POST['item_number']) . "'
					;"
				);
			send_order_emails($order['order_id'], $order['order_status']);
			return true;
			break;

		case 'pending':
			db_query("
					UPDATE	orders
					SET		order_status = '" . db_escape(strtolower($_POST['pending_reason'])) . "',
							transaction_id = '" . db_escape($_POST['txn_id']) . "'
					WHERE	order_id = '" . db_escape($_POST['item_number']) . "'
					;"
				);

			send_order_emails($order['order_id'], $order['order_status']);

			if ( $_POST['pending_reason'] == 'echeck' )
			{
				return true;
			}
			else
			{
				return false;
			}
			break;

		case 'in-progress':
			db_query("
					UPDATE	orders
					SET		order_status = 'pending',
							transaction_id = '" . db_escape($_POST['txn_id']) . "'
					WHERE	order_id = '" . db_escape($_POST['item_number']) . "'
					;"
				);
			send_order_emails($order['order_id'], $order['order_status']);
			return false;
			break;

		case 'partially-refunded':
			db_query("
					UPDATE	orders
					SET		order_status = 'refunded',
							transaction_id = '" . db_escape($_POST['txn_id']) . "'
					WHERE	order_id = '" . db_escape($_POST['item_number']) . "'
					;"
				);
			send_order_emails($order['order_id'], $order['order_status']);
			return false;
			break;

		default:
			db_query("
					UPDATE	orders
					SET		order_status = '" . db_escape(strtolower($_POST['payment_status'])) . "',
							transaction_id = '" . db_escape($_POST['txn_id']) . "'
					WHERE	order_id = '" . db_escape($_POST['item_number']) . "'
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
	db_query("
			UPDATE	users
			SET		user_name = '" . db_escape($_POST['first_name'] . ' ' . $_POST['last_name']) . "',
					user_email = '" . db_escape($_POST['payer_email']) . "',
					user_paypal = '" . db_escape($_POST['payer_email']) . "',
					user_phone = '" . db_escape($_POST['contact_phone']) . "'
			WHERE	user_id = '" . db_escape($user_id) . "'
			AND		user_name = ''
			;"
		);
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
		$sig = "\n"
			. '--  ' . "\n"
			. 'Mike Koepke  ' . "\n"
			. 'support@semiologic.com';

		if ( $order['aff_id'] )
		{
			$res = db_query("
					SELECT	*
					FROM	users
					WHERE	aff_id = '" . db_escape($order['aff_id']) . "'
					;"
				);

			$referrer = db_get_row($res);
		}
		else
		{
			$referrer = null;
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
		case 'echeck':
			if ( $old_status != 'echeck' )
			{
				// notify referrer
				if ( $referrer && $order['aff_commission'] )
				{
					$title = 'You have generated a sale on semiologic.com!';

					$message = $title . "\n\n"
						. 'Transaction: ' . $order['order_id'] . "\n"
						. 'Date: ' . $order['order_date'] . "\n"
						. 'Customer: ' . $customer['user_name'] . "\n"
						. 'Amount: ' . $order['order_amount'] . "\n"
						. 'Commission: ' . $order['aff_commission'] . "\n"
						. "\n"
						. 'To access your affiliate stats:' . "\n"
						. "\n"
						. 'http://oldbackend.semiologic.com/stats.php?user_key=' . $referrer['user_key'] . "\n"
						. "\n"
						. 'Thank you for your continuing support to semiologic.com!' . "\n"
						. $sig;

					send_email($referrer['user_email'], $title, $message, 'sales@semiologic.com');
				}

				// notify customer
				$title = 'Your order on semiologic.com!';

				$message = 'Thank you for your business on semiologic.com!' . "\n\n"
					. 'Please find the Semiologic Pro members area at the following location:' . "\n\n"
					. 'http://wp-pro.semiologic.com' . "\n"
					. 'user: semiologic' . "\n"
					. 'pass: bestvalue' . "\n"
					. "\n"
					. 'To your online success!' . "\n"
					. $sig;

				send_email($customer['user_email'], $title, $message, 'support@semiologic.com');

				// notify sales
				$title = 'New order on semiologic.com!';

				$message = $title . "\n\n"
					. 'Transaction: ' . $order['order_id'] . "\n"
					. 'PP Transaction: ' . $order['transaction_id'] . "\n"
					. 'Date: ' . $order['order_date'] . "\n"
					. 'Customer: ' . $customer['user_name'] . "\n"
					. 'Phone: ' . $customer['user_phone'] . "\n"
					. 'Email: ' . $customer['user_email'] . "\n"
					. 'Amount: ' . $order['order_amount'] . "\n"
					. ( $referrer
						? ( "\n"
							. 'Referrer: ' . $referrer['user_name'] . ' (' . $referrer['aff_id'] . ')' . "\n"
							. 'Phone: ' . $referrer['user_phone'] . "\n"
							. 'Email: ' . $referrer['user_email'] . "\n"
							. 'Commission: ' . $order['aff_commission'] . "\n\n"
							)
						: ''
						)
					. $sig;

				send_email(pp_address, $title, $message, $customer['user_email']);
			}
			else
			{
				// notify sales
				$title = 'Order status update on semiologic.com (payment cleared)!';

				$message = $title . "\n\n"
					. 'Transaction: ' . $order['order_id'] . "\n"
					. 'PP Transaction: ' . $order['transaction_id'] . "\n"
					. 'Date: ' . $order['order_date'] . "\n"
					. 'Customer: ' . $customer['user_name'] . "\n"
					. 'Phone: ' . $customer['user_phone'] . "\n"
					. 'Email: ' . $customer['user_email'] . "\n"
					. 'Amount: ' . $order['order_amount'] . "\n"
					. ( $referrer
						? ( "\n"
							. 'Referrer: ' . $referrer['user_name'] . ' (' . $referrer['aff_id'] . ')' . "\n"
							. 'Phone: ' . $referrer['user_phone'] . "\n"
							. 'Email: ' . $referrer['user_email'] . "\n"
							. 'Commission: ' . $order['aff_commission'] . "\n\n"
							)
						: ''
						)
					. $sig;

				send_email(pp_address, $title, $message, $customer['user_email']);
			}
			break;

		default:
			// notify affiliate
			if ( $referrer && $order['aff_commission']
				&& in_array($new_status, array('denied', 'failed', 'refunded', 'reversed', 'voided'))
				)
			{
				$title = 'The sale you generated on semiologic.com has been reversed!';

				$message = $title . "\n\n"
					. 'Transaction: ' . $order['order_id'] . "\n"
					. 'Date: ' . $order['order_date'] . "\n"
					. 'Customer: ' . $customer['user_name'] . "\n"
					. 'Amount: ' . $order['order_amount'] . "\n"
					. 'Commission: ' . $order['aff_commission'] . "\n" . "\n"
					. "\n"
					. 'To access your affiliate stats:' . "\n"
					. "\n"
					. 'http://oldbackend.semiologic.com/stats.php?user_key=' . $referrer['user_key'] . "\n"
					. "\n"
					. 'Thank you for your continuing support to semiologic.com!' . "\n"
					. $sig;

				send_email($referrer['user_email'], $title, $message, 'sales@semiologic.com');
			}

			// notify sales
			$title = 'Order on semiologic.com in need of investigation!';

			$message = $title . "\n\n"
				. 'Transaction: ' . $order['order_id'] . "\n"
				. 'New Status: ' . $new_status . "\n"
				. 'PP Transaction: ' . $order['transaction_id'] . "\n"
				. 'Date: ' . $order['order_date'] . "\n"
				. 'Amount: ' . $order['order_amount'] . "\n"
				. 'Customer: ' . $customer['user_name'] . "\n"
				. 'Phone: ' . $customer['user_phone'] . "\n"
				. 'Email: ' . $customer['user_email'] . "\n"
				. ( $referrer
					? ( "\n"
						. 'Referrer: ' . $referrer['user_name'] . ' (' . $referrer['aff_id'] . ')' . "\n"
						. 'Phone: ' . $referrer['user_phone'] . "\n"
						. 'Email: ' . $referrer['user_email'] . "\n"
						. 'Commission: ' . $order['aff_commission'] . "\n\n"
						)
					: ''
					)
				. $sig;

			send_email(pp_address, $title, $message, $customer['user_email']);
		}
	}
} # send_order_emails()


#
# send_email()
#

function send_email($to, $title, $message, $from)
{
	$headers = "From: $from\r\n"
		. "Reply-To: <$from>\r\n"
		. "Return-Path: <$from>\r\n"
		. "X-Sender: $from\r\n"
		. "X-Mailer: PHP/" . phpversion();

	return mail(
		$to,
		$title,
		$message,
		$headers,
		"-f $from"
		);
} # send_email()


#
# display_order_details()
#

function display_order_details()
{
?>
<div style="margin: 30px; padding: 30px; border: solid 1px black; font-family: Verdana;">

<h1>Semiologic Pro</h1>

<p>Thank you for your business!</p>

<p>Please find the Semiologic Pro members area at the following location:</p>

<ul>
	<li><a href="http://wp-pro.semiologic.com" target="_blank">http://wp-pro.semiologic.com</a></li>
	<li>user: semiologic</li>
	<li>pass: bestvalue</li>
</ul>

<p>Your paypal address will receive a copy of these download instructions as well. They sometimes get caught by spam filters, so be sure to check in your junk mail folder as well.</p>

<p>Mike Koepke<br />
support at semiologic dot com</p>

</div>
<?php
} # display_order_details()


#
# display_order_error()
#

function display_order_error()
{
?>
<div style="margin: 30px; padding: 30px; border: solid 1px black; font-family: Verdana;">

<h1>Semiologic Pro</h1>

<p>A network lag is apparently preventing PayPal from instantly validating your transaction. It will usually resolve by itself in a few minutes, giving you access to members.semiologic.com using the user details you signed up with.</p>

<p>Please try again in a few minutes. If it's not working within the hour, please email support at semiologic dot com. You can alternatively reach me on my <a href="http://www.semiologic.com/about/">cell phone</a> (please mind the time zone if you do).</p>

<p>Mike Koepke<br />
support at semiologic dot com</p>

</div>
<?php
} # display_order_error()

?>
