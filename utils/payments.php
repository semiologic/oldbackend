<?php
require_once tmp_mc_path . '/utils/session.php';
require_user();


#
# admin_payments()
#

function admin_payments()
{
	include_once tmp_mc_path . '/header.php';

	echo '<h1>'
		. 'Affiliate Commissions'
		. '</h1>' . "\n";

	$res = db_query("
			SELECT	*
			FROM	total_pending_commissions
			ORDER BY aff_id,
				order_date
		");

	$i = 0;

	echo '<h2>Pending Commissions</h2>' . "\n";

	echo '<table class="datagrid">' . "\n";

	echo '<tr align="center" class="hd">' . "\n"
		. '<th width="80">'
		. 'Date'
		. '</th>'
		. '<th>'
		. 'Transaction'
		. '</th>'
		. '<th width="80">'
		. 'Amount'
		. '</th>'
		. '<th width="80">'
		. 'Commission'
		. '</th>'
		. '</tr>' . "\n";

	if ( $row = db_get_row($res) )
	{
		do
		{
			echo '<tr align="center"' . ( $i%2 ? ' class="alt"' : '' ) . '>'
				. '<td>'
				. date('M jS<b\r />Y', strtotime($row['order_date']))
				. '</td>'
				. '<td align="left">'
				. $row['order_key']
				. '</td>'
				. '<td align="right">'
				. number_format($row['order_amount'], 2)
				. '</td>'
				. '<td align="right">'
				. number_format($row['aff_commission'], 2)
				. '</td>'
				. '</tr>';

			$prev_row = $row;
			$i++;

			$row = db_get_row($res);

			if ( !$row || $prev_row['aff_id'] != $row['aff_id'] )
			{
				echo '<tr class="ft">'
					. '<td colspan="3">'
					. '<a href="mailto:' . $prev_row['aff_email'] . '">'
					. $prev_row['aff_name'] . '</a>'
					. ( $prev_row['aff_phone']
						? ( ' (' . $prev_row['aff_phone'] . ')' )
						: ''
						)
					. ( $prev_row['aff_payment_status']
						? ' (<strong>' . $prev_row['aff_payment_status'] . '</strong>)'
						: ''
						)
					. '</td>'
					. '<td align="right"><b>'
					. '<a href="pay.php?aff_id=' . $prev_row['aff_id'] . '"'
						. ( $prev_row['aff_payment_status']
							? ' style="font-weight: bold; color: firebrick;"'
							: ''
							)
						. '>'
					. number_format($prev_row['total_commissions'], 2)
					. '</a>'
					. '</b></td>'
					. '</tr>';
			}
		} while ( $row );
	}

	echo '</table>';

	include_once tmp_mc_path . '/footer.php';
} # admin_payments()


#
# display_payments()
#

function display_payments()
{
	include_once tmp_mc_path . '/header.php';

	echo '<h1>'
		. 'Affiliate Commissions'
		. '</h1>' . "\n";

	echo '<p>Affiliate commissions get paid 31 days after the transactions occur, on the first business day of each month.</p>' . "\n";

	$res = db_query("
			SELECT	*
			FROM	total_pending_commissions
			WHERE	aff_id = '" . db_escape($_SESSION['user_id']) . "'
			ORDER BY order_date
		");

	$i = 0;

	if ( $row = db_get_row($res) )
	{
		echo '<h2>Pending Commissions</h2>' . "\n";

		echo '<p>You will receive a payment for the following transactions shortly.</p>' . "\n";

		echo '<table class="datagrid">' . "\n";

		echo '<tr align="center" class="hd">' . "\n"
			. '<th width="80">'
			. 'Date'
			. '</th>'
			. '<th>'
			. 'Transaction'
			. '</th>'
			. '<th width="80">'
			. 'Amount'
			. '</th>'
			. '<th width="80">'
			. 'Commission'
			. '</th>'
			. '</tr>' . "\n";

		do
		{
			echo '<tr align="center"' . ( $i%2 ? ' class="alt"' : '' ) . '>'
				. '<td>'
				. date('M jS<b\r />Y', strtotime($row['order_date']))
				. '</td>'
				. '<td align="left">'
				. $row['order_key']
				. '</td>'
				. '<td align="right">'
				. number_format($row['order_amount'], 2)
				. '</td>'
				. '<td align="right">'
				. number_format($row['aff_commission'], 2)
				. '</td>'
				. '</tr>';

			$prev_row = $row;
			$i++;

			$row = db_get_row($res);

			if ( !$row )
			{
				echo '<tr class="ft">'
					. '<td colspan="3">&nbsp;</td>'
					. '<td align="right"><b>'
					. number_format($prev_row['total_commissions'], 2)
					. '</b></td>'
					. '</tr>';
			}
		} while ( $row );

		echo '</table>';
	}


	$res = db_query("
			SELECT	*
			FROM	total_upcoming_commissions
			WHERE	aff_id = '" . db_escape($_SESSION['user_id']) . "'
			ORDER BY order_date
		");

	$i = 0;

	if ( $row = db_get_row($res) )
	{
		echo '<h2>Upcoming Commissions</h2>' . "\n";

		echo '<table class="datagrid">' . "\n";

		echo '<tr align="center" class="hd">' . "\n"
			. '<th width="80">'
			. 'Date'
			. '</th>'
			. '<th>'
			. 'Transaction'
			. '</th>'
			. '<th width="80">'
			. 'Amount'
			. '</th>'
			. '<th width="80">'
			. 'Commission'
			. '</th>'
			. '</tr>' . "\n";

		do
		{
			echo '<tr align="center"' . ( $i%2 ? ' class="alt"' : '' ) . '>'
				. '<td>'
				. date('M jS<b\r />Y', strtotime($row['order_date']))
				. '</td>'
				. '<td align="left">'
				. $row['order_key']
				. '</td>'
				. '<td align="right">'
				. number_format($row['order_amount'], 2)
				. '</td>'
				. '<td align="right">'
				. number_format($row['aff_commission'], 2)
				. '</td>'
				. '</tr>';

			$prev_row = $row;
			$i++;

			$row = db_get_row($res);

			if ( !$row )
			{
				echo '<tr class="ft">'
					. '<td colspan="3">&nbsp;</td>'
					. '<td align="right"><b>'
					. number_format($prev_row['total_commissions'], 2)
					. '</b></td>'
					. '</tr>';
			}
		} while ( $row );

		echo '</table>';
	}


	$res = db_query("
			SELECT	*
			FROM	total_paid_commissions
			WHERE	aff_id = '" . db_escape($_SESSION['user_id']) . "'
			ORDER BY aff_commission_paid DESC
		");

	$i = 0;

	if ( $row = db_get_row($res) )
	{
		echo '<h2>Paid Commissions</h2>';

		echo '<table class="datagrid">' . "\n";

		echo '<tr align="center" class="hd">' . "\n"
			. '<th width="80">'
			. 'Date'
			. '</th>'
			. '<th>'
			. 'Transaction'
			. '</th>'
			. '<th width="80">'
			. 'Amount'
			. '</th>'
			. '<th width="80">'
			. 'Commission'
			. '</th>'
			. '</tr>' . "\n";

		do
		{
			echo '<tr align="center"' . ( $i%2 ? ' class="alt"' : '' ) . '>'
				. '<td>'
				. date('M jS<b\r />Y', strtotime($row['order_date']))
				. '</td>'
				. '<td align="left">'
				. $row['order_key']
				. '</td>'
				. '<td align="right">'
				. number_format($row['order_amount'], 2)
				. '</td>'
				. '<td align="right">'
				. number_format($row['aff_commission'], 2)
				. '</td>'
				. '</tr>';

			$prev_row = $row;

			$row = db_get_row($res);

			if ( !$row || $prev_row['aff_commission_paid'] != $row['aff_commission_paid'] )
			{
				echo '<tr class="ft">'
					. '<td colspan="3" align="left"><b>'
						. 'Paid on '
						. date('M jS, Y', strtotime($prev_row['aff_commission_paid']))
						. '</b></td>'
					. '<td align="right"><b>'
					. number_format($prev_row['total_commissions'], 2)
					. '</b></td>'
					. '</tr>';
			}
		} while ( $row );

		echo '</table>';
	}

	include_once tmp_mc_path . '/footer.php';
} # display_payments()
?>