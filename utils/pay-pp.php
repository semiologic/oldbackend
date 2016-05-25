<?php
require_once tmp_mc_path . '/utils/session.php';


#
# validate_payment()
#

function validate_payment()
{
	@session_start();

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

	list($aff_id, $transaction_key) = split('-', $_POST['item_number']);

	$res = db_query("
		SELECT	*
		FROM	pending_aff_payments
		WHERE	aff_id = '" . db_escape($aff_id) . "'
		LIMIT 1
		");

	if ( $row = db_get_row($res) )
	{
		$aff_paypal = $row['aff_paypal'];
		$total_commissions = $row['total_commissions'];

		$res = db_query("
			SELECT	*
			FROM	users
			WHERE	user_id = '" . db_escape($aff_id) . "'
			");

		$aff = db_get_row($res);

		if ( $_SERVER['HTTP_HOST'] == 'localhost' )
		{
			$_POST['business'] = $aff_paypal;
			$_POST['mc_currency'] = 'USD';
			$_POST['txn_id'] = md5(time() + rand());
			$_POST['mc_gross'] = $total_commissions;
			$_POST['payment_status'] = 'completed';
		}

		if ( $_POST['business'] == $aff_paypal
			&& $_POST['mc_currency'] == 'USD'
			&& $_POST['mc_gross'] == $total_commissions
			)
		{
			switch ( strtolower($_POST['payment_status']) )
			{
			case 'completed':
			case 'cleared':
			case 'canceled-reversal':
			case 'cancelled-reversal':
				db_query("
					UPDATE	orders
					SET		aff_payment_status =
							CASE
							WHEN aff_id = '" . db_escape($aff_id) . "'
							THEN
								'cleared'
							ELSE
								aff_payment_status
							END,
							tier_payment_status =
							CASE
							WHEN tier_id = '" . db_escape($aff_id) . "'
							THEN
								'cleared'
							ELSE
								tier_payment_status
							END
					WHERE	order_id IN (
							SELECT	order_id
							FROM	pending_aff_payments
							WHERE	aff_id = '" . db_escape($aff_id) . "'
							)
					");

				// notify affiliate
				$title = 'You\'ve received an affiliate payment from semiologic.com';

				$message = $title . "\n\n"
					. 'Amount: ' . $total_commissions . "\n"
					. "\n"
					. 'Details:' . "\n"
					. "\n"
					. 'http://oldbackend.semiologic.com/payments.php?user_key=' . $aff['user_key'] . "\n"
					. "\n"
					. 'Thank you for your continuing support to semiologic.com!' . "\n"
					. email_sig;

				send_email($aff['user_email'], $title, $message, 'sales@semiologic.com');
				break;

			default:
				if ( strtolower($_POST['payment_status']) == 'pending' )
				{
					$status = $_POST['pending_reason'];
				}
				else
				{
					$status = $_POST['payment_status'];
				}

				db_query("
					UPDATE	orders
					SET		aff_payment_status =
							CASE
							WHEN aff_id = '" . db_escape($aff_id) . "'
							THEN
								'" . db_escape($status) . "'
							ELSE
								aff_payment_status
							END,
							tier_payment_status =
							CASE
							WHEN tier_id = '" . db_escape($aff_id) . "'
							THEN
								'" . db_escape($status) . "'
							ELSE
								tier_payment_status
							END
					WHERE	order_id IN (
							SELECT	order_id
							FROM	pending_aff_payments
							WHERE	aff_id = '" . db_escape($aff_id) . "'
							)
					");

				// notify sales
				$title = 'Affiliate payment on semiologic.com in need of investigation!';

				$message = $title . "\n\n"
					. 'PP Transaction: ' . $_POST['txn_id'] . "\n"
					. 'Status: ' . $status . "\n"
					. 'Amount: ' . $total_commissions . "\n"
					. "\n"
					. 'Affiliate: ' . $aff['user_name'] . "\n"
					. 'Phone: ' . $aff['user_phone'] . "\n"
					. 'Email: ' . $aff['user_email'] . "\n";

				send_email(pp_address, $title, $message, $aff['user_email']);
				break;
			}
		}
		else
		{
			// notify sales
			$title = 'Affiliate payment on semiologic.com in need of investigation!';

			$message = $title . "\n\n"
				. 'PP Transaction: ' . $_POST['txn_id'] . "\n"
				. 'Status: ' . $_POST['payment_status'] . "\n"
				. 'Amount: ' . $total_commissions . "\n"
				. "\n"
				. 'Affiliate: ' . $aff['user_name'] . "\n"
				. 'Phone: ' . $aff['user_phone'] . "\n"
				. 'Email: ' . $aff['user_email'] . "\n";

			send_email(pp_address, $title, $message, $aff['user_email']);
		}
	}

	if ( $_SESSION['is_admin'] )
	{
		do_redirect('payments.php');
	}
	else
	{
		die;
	}
} # validate_payment()
?>