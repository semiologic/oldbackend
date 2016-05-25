<?php
require_once tmp_mc_path . '/utils/session.php';
require_user();


#
# display_orders()
#

function display_orders()
{
	include_once tmp_mc_path . '/header.php';


	if ( !$_SESSION['is_admin'] )
	{
		$res = db_query("
				SELECT	detailed_order_stats.*
				FROM	detailed_order_stats
				WHERE	user_id = '" . db_escape($_SESSION['user_id']) . "'
				ORDER BY order_date DESC, product_name
			");
	}
	else
	{
		$res = db_query("
				SELECT	detailed_order_stats.*
				FROM	detailed_order_stats
				WHERE	date_trunc('month', order_date) IN (
					SELECT	DISTINCT date_trunc('month', order_date) as order_month
					FROM	order_stats
					ORDER BY order_month DESC
					LIMIT 3
					)
				ORDER BY order_date DESC, product_name
			");
	}


	echo '<h1>'
		. 'Financials'
		. '</h1>' . "\n";

	echo '<table class="datagrid">' . "\n";

	echo '<tr align="center" class="hd">' . "\n"
		. '<th width="80">'
		. 'Date'
		. '</th>'
		. '<th>'
		. 'Items'
		. '</th>'
		. '<th width="80">'
		. 'Cost'
		. '</th>'
		. '</tr>' . "\n";

	if ( $row = db_get_row($res) )
	{
		$i = 0;

		do
		{
			echo '<tr align="center"' . ( $i%2 ? ' class="alt"' : '' ) . '>' . "\n"
				. ( ( !isset($prev_row) || $prev_row['order_id'] != $row['order_id'] )
					? ( '<td rowspan="' . ( $row['num_items'] + 1 ) . '">' . date('M dS<b\r>Y', strtotime($row['order_date'])) . '</td>' )
					: ''
					)
				. '<td align="left">'
				. $row['product_name']
				. ( $row['product_discount']
					? ( ' (' . $row['product_discount'] . '% off)' )
					: ''
					)
				. '</td>'
				. '<td align="right">'
				. number_format($row['product_price'], 2)
				. '</td>'
				. '</tr>' . "\n";

			$prev_row = $row;

			$row = db_get_row($res);

			if ( !$row || $row['order_id'] != $prev_row['order_id'] )
			{
				echo '<tr align="center"' . ( $i%2 ? ' class="alt"' : '' ) . '>' . "\n"
					. '<td align="left" style="border-top: solid 1px black;">'
					. ( $_SESSION['is_admin']
						? ( '<a href="mailto:' . $prev_row['user_email'] . '">' . $prev_row['user_name'] . '</a>'
							. ( $prev_row['user_phone']
								? ( ' (' . $prev_row['user_phone'] . ')' )
								: ''
								)
							. '<br />'
							)
						: ''
						)
					. $prev_row['order_key']
					. '</td>'
					. '<td align="right" style="border-top: solid 1px dimgray;"><b>' . number_format($prev_row['order_amount'], 2) . '</b></td>'
					. '</tr>' . "\n";

				if ( $row )
				{
					echo '<tr class="ft">'
						. '<td colspan="3" height="2"></td>'
						. '</tr>' . "\n";
				}

				$i++;
			}
		} while ( $row );
	}

	echo '</table>' . "\n";

	include_once tmp_mc_path . '/footer.php';
} # display_orders()
?>