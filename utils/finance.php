<?php
require_once tmp_mc_path . '/utils/session.php';
require_admin();


#
# display_finance()
#

function display_finance()
{
	include_once tmp_mc_path . '/header.php';


	$res = db_query("
			SELECT	date_trunc('month', order_date) as order_date,
					count(order_id) as num_orders,
					sum(
					CASE order_status IN ('canceled_reversal', 'cleared', 'completed')
					WHEN true
					THEN
						1
					ELSE
						0
					END) as num_cleared,
					sum(
					CASE order_status NOT IN ('canceled_reversal', 'cleared', 'completed')
					WHEN true
					THEN
						1
					ELSE
						0
					END) as num_reversed,
					sum(
					CASE order_status IN ('canceled_reversal', 'cleared', 'completed')
					WHEN true
					THEN
						order_amount
					ELSE
						0
					END) as total_amount,
					sum(
					CASE order_status IN ('canceled_reversal', 'cleared', 'completed')
					WHEN true
					THEN
						COALESCE(aff_commission, 0) + COALESCE(tier_commission, 0)
					ELSE
						0
					END) as total_commissions
			FROM	orders
			WHERE	order_status <> 'pending'
			GROUP BY date_trunc('month', order_date)
			ORDER BY date_trunc('month', order_date) DESC
		");
/*
	"canceled_reversal"
	"cancelled"
	"cleared"
	"completed"
	"denied"
	"pending"
	"refunded"
	"reversed"
*/
	echo '<h1>'
		. 'Financials'
		. '</h1>' . "\n";

	echo '<table class="datagrid">' . "\n";

	echo '<tr align="center" class="hd">' . "\n"
		. '<th width="80">'
		. 'Month'
		. '</th>'
		. '<th>'
		. 'Orders (Cleared / Reversed)'
		. '</th>'
		. '<th>'
		. 'Cleared Amount'
		. '</th>'
		. '<th>'
		. 'Commissions'
		. '</th>'
		. '</tr>' . "\n";

	if ( $row = db_get_row($res) )
	{
		$i = 0;

		do
		{
			echo '<tr align="center"' . ( $i%2 ? ' class="alt"' : '' ) . '>' . "\n"
				. '<td>' . date('M, Y', strtotime($row['order_date'])) . '</td>'
				. '<td>'
				. $row['num_orders'] . ' ( ' . $row['num_cleared'] . ' / ' . $row['num_reversed'] . ' )'
				. '</td>'
				. '<td align="right">'
				. number_format($row['total_amount'], 2)
				. '</td>'
				. '<td align="right">'
				. number_format($row['total_commissions'], 2)
				. '</td>'
				. '</tr>' . "\n";

			$prev_row = $row;

			$row = db_get_row($res);
			$i++;
		} while ( $row );
	}

	echo '</table>' . "\n";

	include_once tmp_mc_path . '/footer.php';
} # display_finance()
?>