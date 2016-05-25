<?php
require_once tmp_mc_path . '/utils/session.php';
require_admin();


#
# do_checkout()
#

function process_payment()
{
	$res = db_query("
		SELECT	*
		FROM	total_pending_commissions
		WHERE	aff_id = '" . db_escape($_GET['aff_id']) . "'
		");

	if ( ( $row = db_get_row($res) ) )
	{
		db_query("
			UPDATE	orders
			SET		aff_payment_status =
					CASE
					WHEN aff_id = '" . db_escape($_GET['aff_id']) . "'
					THEN
						'pending'
					ELSE
						aff_payment_status
					END,
					tier_payment_status =
					CASE
					WHEN tier_id = '" . db_escape($_GET['aff_id']) . "'
					THEN
						'pending'
					ELSE
						tier_payment_status
					END
			WHERE	order_id IN (
					SELECT	order_id
					FROM	pending_commissions
					WHERE	aff_id = '" . db_escape($_GET['aff_id']) . "'
					)
			");

		$total_commissions = $row['total_commissions'];
		$aff_paypal = $row['aff_paypal'];
		$transaction_key = $_GET['aff_id'];

		do
		{
			$transaction_key .= '-' . $row['order_key'];
		} while ( $row = db_get_row($res) );

		$transaction_key = $_GET['aff_id'] . '-' . md5($transaction_key);

		#echo '<pre>';
		#var_dump($aff_paypal, $amount, $transaction_key);
		#echo '</pre>';
		#die;

		$location = 'https://' . pp_url . '/cgi-bin/webscr'
			. '?cmd=_xclick'
			. '&business=' . urlencode($aff_paypal)
			. '&amount=' . $total_commissions
			. '&currency_code=USD'
			. '&item_name=' . urlencode('Affiliate commissions from semiologic.com')
			. '&item_number=' . urlencode($transaction_key)
			. '&no_shipping=1'
			. '&no_note=1'
			. '&notify_url=' . urlencode('http://oldbackend.semiologic.com/pay-pp.php')
			. '&return=' . urlencode('http://oldbackend.semiologic.com/pay-pp.php')
			. '&charset=UTF-8';

		#var_dump($location);
		#var_dump($amount);

		if ( $_SERVER['HTTP_HOST'] != 'localhost' )
		{
			do_redirect($location);
		}
		else
		{
			do_redirect('pay-pp.php?transaction=' . urlencode($transaction_key));
		}
	}
	else
	{
		do_redirect('payments.php');
	}
} # process_payment()
?>