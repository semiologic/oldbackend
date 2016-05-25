<?php
require_once tmp_mc_path . '/utils/session.php';
require_user();


#
# display_stats()
#

function display_stats()
{
	include_once tmp_mc_path . '/header.php';

	echo '<h1>Affiliate Campaign Stats</h1>';

	if ( isset($_GET['date']) && preg_match("/^\d{4}-\d{2}$/", $_GET['date']) )
	{
		display_detailed_stats();
	}
	else
	{
		display_aggregate_stats();
	}

	include_once tmp_mc_path . '/footer.php';
} # display_stats


#
# display_aggregate_stats()
#

function display_aggregate_stats()
{
	if ( isset($_GET['date']) && preg_match("/^\d{4}$/", $_GET['date']) )
	{
		$year = $_GET['date'] . '-01-01';
	}
	else
	{
		unset($_GET['date']);
	}

	if ( !$_SESSION['is_admin'] )
	{
		$res = db_query("
				SELECT	DISTINCT stats_year
				FROM	aggregate_affiliate_stats
				WHERE	aff_id = '" . db_escape($_SESSION['user_id']) . "'
				ORDER BY stats_year DESC
			");
	}
	else
	{
		$res = db_query("
				SELECT	DISTINCT stats_year
				FROM	aggregate_affiliate_stats
				ORDER BY stats_year DESC
			");
	}

	$date_selector = '<select onchange="document.location = this.value">';

	while ( $row = db_get_row($res) )
	{
		$y = date('Y', strtotime($row['stats_year']));

		if ( !isset($year) )
		{
			$_GET['date'] = $y;
			$year = $_GET['date'] . '-01-01';
		}

		$date_selector .= '<option'
				. ' value="?date=' . $y . '"'
				. ( isset($_GET['date']) && $_GET['date'] == $y ? ' selected="selected"' : '' )
				. '>'
			. date('Y', strtotime($row['stats_year']))
			. '</option>';
	}

	if ( !isset($year) )
	{
		$_GET['date'] = date('Y');
		$year = $_GET['date'] . '-01-01';
	}

	$date_selector .= '</select>';

	if ( !$_SESSION['is_admin'] )
	{
		$res = db_query("
				SELECT	aggregate_affiliate_stats.*
				FROM	aggregate_affiliate_stats
				WHERE	stats_year = '" . db_escape($year) . "'
				AND		aff_id = '" . db_escape($_SESSION['user_id']) . "'
				ORDER BY is_owner DESC,
					campaign_key,
					stats_month
			");
	}
	else
	{
		$res = db_query("
				SELECT	campaign_id,
						campaign_key,
						MAX ( is_owner ) as is_owner,
						stats_month,
						stats_year,
						num_visitors,
						num_orders,
						total_amount,
						order_commissions,
						order_commissions_paid,
						aff_id,
						aff_commission,
						aff_commission_paid
				FROM	aggregate_affiliate_stats
				WHERE	stats_year = '" . db_escape($year) . "'
				GROUP BY campaign_id,
						campaign_key,
						stats_month,
						stats_year,
						num_visitors,
						num_orders,
						total_amount,
						order_commissions,
						order_commissions_paid,
						aff_id,
						aff_commission,
						aff_commission_paid
				HAVING	MAX ( is_owner ) > 0
				ORDER BY is_owner DESC,
					campaign_key,
					stats_month
			");
	}

	if ( $row = db_get_row($res) )
	{
		do
		{
			if ( !isset($prev_row) || $prev_row['campaign_id'] != $row['campaign_id'] )
			{
				echo '<h2>' . "\n"
					. '<span style="float: right;">' . $date_selector . '</span>' . "\n"
					. ( $row['campaign_key'] ? $row['campaign_key'] : 'Direct Sales' )
					. '<br />' . "\n"
					. '<span style="font-size: small; font-weight: normal;">'
					. ( !$_SESSION['is_admin']
						? ( ( $row['is_owner']
								? 'Affiliate Campaign'
								: 'Tier Affiliate Campaign'
								)
							)
						: ( '<a href="mailto:' . $row['aff_email'] . '">' . $row['aff_name'] . '</a>'
							. ( $row['aff_phone']
								? ( ' (' . $row['aff_phone'] . ')' )
								: ''
								)
							. ( $row['tier_id']
								? ( ' / <a href="mailto:' . $row['tier_email'] . '">' . $row['tier_name'] . '</a>'
									. ( $row['tier_phone']
										? ( ' (' . $row['tier_phone'] . ')' )
										: ''
										)
									)
								: ''
								)
							)
						)
					. '</span>'
					. '</h2>' . "\n";

				echo '<table class="datagrid">' . "\n";

				echo '<tr align="center" class="hd">' . "\n"
					. '<th width="80">'
					. 'Month'
					. '</th>'
					. '<th width="60">'
					. 'Visitors'
					. '</th>'
					. '<th width="60">'
					. 'Orders'
					. '</th>'
					. '<th>'
					. 'Transactions'
					. '</th>'
					. '<th width="80">'
					. 'Commissions'
					. '</th>'
					. '<th width="80">'
					. 'Paid'
					. '</th>'
					. '</tr>' . "\n";

				$total_visitors = 0;
				$total_orders = 0;
				$total_amount = 0;
				$total_commissions = 0;
				$total_commissions_paid = 0;

				$i = 0;
			}

			$m = date('Y-m', strtotime($row['stats_month']));

			$total_visitors += $row['num_visitors'];
			$total_orders += $row['num_orders'];
			$total_amount += $row['total_amount'];

			if ( !$_SESSION['is_admin'] )
			{
				$aff_commission = $row['aff_commission'];
				$aff_commission_paid = $row['aff_commission_paid'];
			}
			else
			{
				$aff_commission = $row['order_commissions'];
				$aff_commission_paid = $row['order_commissions_paid'];
			}

			$total_commissions += $aff_commission;
			$total_commissions_paid += $aff_commission_paid;

			echo '<tr align="center"' . ( $i%2 ? ' class="alt"' : '' )
					. ' style="cursor: pointer;"'
					. ' onclick="document.location = \'?date=' . $m . '&amp;campaign_id=' . $row['campaign_id'] . '\'"'
					. ' onmouseover="this.style.backgroundColor=\'#ffeecc\'"'
					. ' onmouseout="this.style.backgroundColor=\'\'"'
					. '>'
				. '<td>'
				. date('M, Y', strtotime($row['stats_month']))
				. '</td>'
				. '<td>'
				. $row['num_visitors']
				. '</td>'
				. '<td>'
				. $row['num_orders']
				. '</td>'
				. '<td align="right">'
				. number_format($row['total_amount'], 2)
				. '</td>'
				. '<td align="right">'
				. number_format($aff_commission, 2)
				. '</td>'
				. '<td align="right">'
				. number_format($aff_commission_paid, 2)
				. '</td>'
				. '</tr>';

			$prev_row = $row;
			$i++;

			$row = db_get_row($res);

			if ( !$row || $prev_row['campaign_id'] != $row['campaign_id'] )
			{
				echo '<tr align="center" class="ft">' . "\n"
					. '<td><b>' . date('Y', strtotime($prev_row['stats_year'])) . '</b></td>'
					. '<td><b>' . $total_visitors . '</b></td>'
					. '<td><b>' . $total_orders . '</b></td>'
					. '<td align="right"><b>'
						. ( $total_amount
							? number_format($total_amount, 2)
							: '&nbsp;'
							)
						. '</b></td>'
					. '<td align="right"><b>'
						. ( ( $total_amount )
							? ( number_format($total_commissions, 2) )
							: '&nbsp;'
							)
						. '</b></td>'
					. '<td align="right"><b>'
						. ( ( $total_amount )
							? ( number_format($total_commissions_paid, 2) )
							: '&nbsp;'
							)
						. '</b></td>'
					. '</tr>' . "\n";

				echo '</table>';
			}

		} while ( $row );
	}
} # display_aggregate_stats()


#
# display_detailed_stats()
#

function display_detailed_stats()
{
	$month = $_GET['date'] . '-01';

	if ( !$_SESSION['is_admin'] )
	{
		$res = db_query("
				SELECT	DISTINCT stats_month
				FROM	aggregate_affiliate_stats
				WHERE	campaign_id = '" . db_escape($_GET['campaign_id']) . "'
				AND		aff_id = '" . db_escape($_SESSION['user_id']) . "'
				ORDER BY stats_month DESC
			");
	}
	else
	{
		$res = db_query("
				SELECT	DISTINCT stats_month
				FROM	aggregate_affiliate_stats
				WHERE	" . ( $_GET['campaign_id']
					? ( "campaign_id = '" . db_escape($_GET['campaign_id']) . "'" )
					: "campaign_id IS NULL"
					)
				. "
				ORDER BY stats_month DESC
			");
	}

	$date_selector = '<select onchange="document.location = this.value">'
		. '<option value="?">'
		. 'Overview'
		. '</option>';

	while ( $row = db_get_row($res) )
	{
		$m = date('Y-m', strtotime($row['stats_month']));

		$date_selector .= '<option'
				. ' value="?date=' . $m . '&amp;campaign_id=' . $_GET['campaign_id']. '"'
				. ( isset($_GET['date']) && $_GET['date'] == $m ? ' selected="selected"' : '' )
				. '>'
			. date('M, Y', strtotime($row['stats_month']))
			. '</option>';
	}

	$date_selector .= '</select>';

	if ( !$_SESSION['is_admin'] )
	{
		$res = db_query("
				SELECT	*,
						CASE
						WHEN aff_id = '" . db_escape($_SESSION['user_id']) . "'
						THEN
							1
						ELSE
							0
						END as is_owner
				FROM	detailed_campaign_stats
				WHERE	date_trunc('month', stats_date) = '" . db_escape($month) . "'
				AND		campaign_id = '" . db_escape($_GET['campaign_id']) . "'
				AND		( aff_id = '" . db_escape($_SESSION['user_id']) . "'
						OR tier_id = '" . db_escape($_SESSION['user_id']) . "'
						)
				ORDER BY stats_date, order_date
			");
	}
	else
	{
		$res = db_query("
				SELECT	detailed_campaign_stats.*
				FROM	detailed_campaign_stats
				WHERE	date_trunc('month', stats_date) = '" . db_escape($month) . "'
				" . ( $_GET['campaign_id']
					? ( "AND campaign_id = '" . db_escape($_GET['campaign_id']) . "'" )
					: ( "AND campaign_id IS NULL" )
					)
				. "
				ORDER BY stats_date, order_date
			");
	}


	if ( $row = db_get_row($res) )
	{
		do
		{
			if ( !isset($prev_row) || $prev_row['campaign_key'] != $row['campaign_key'] )
			{
				echo '<h2>' . "\n"
					. '<span style="float: right;">' . $date_selector . '</span>' . "\n"
					. ( $row['campaign_key'] ? $row['campaign_key'] : 'Direct Sales' )
					. '<br />' . "\n"
					. '<span style="font-size: small; font-weight: normal;">'
					. ( !$_SESSION['is_admin']
						? ( ( $row['is_owner']
								? 'Affiliate Campaign'
								: 'Tier Affiliate Campaign'
								)
							)
						: ( '<a href="mailto:' . $row['aff_email'] . '">' . $row['aff_name'] . '</a>'
							. ( $row['aff_phone']
								? ( ' (' . $row['aff_phone'] . ')' )
								: ''
								)
							. ( $row['tier_id']
								? ( ' / <a href="mailto:' . $row['tier_email'] . '">' . $row['tier_name'] . '</a>'
									. ( $row['tier_phone']
										? ( ' (' . $row['tier_phone'] . ')' )
										: ''
										)
									)
								: ''
								)
							)
						)
					. '</span>'
					. '</h2>' . "\n";

				echo '<table class="datagrid">' . "\n";

				echo '<tr align="center" class="hd">' . "\n"
					. '<th width="80">'
					. 'Date'
					. '</th>'
					. '<th width="60">'
					. 'Visitors'
					. '</th>'
					. '<th width="60">'
					. 'Orders'
					. '</th>'
					. '<th>'
					. 'Transactions'
					. '</th>'
					. '<th width="80">'
					. 'Commissions'
					. '</th>'
					. '<th width="80">'
					. 'Paid'
					. '</th>'
					. '</tr>' . "\n";

				$total_visitors = 0;
				$total_orders = 0;

				$total_amount = 0;
				$total_commissions = 0;
				$total_commissions_paid = 0;

				$i = 0;
			}

			if ( !isset($prev_row) || $prev_row['stats_date'] != $row['stats_date'] )
			{
				$total_visitors += $row['num_visitors'];
				$total_orders += $row['num_orders'];
			}

			$total_amount += $row['order_amount'];

			if ( $_SESSION['is_admin'] )
			{
				$aff_commission = $row['aff_commission'] + $row['tier_commission'];

				$aff_commission_paid = '';

				if ( $row['aff_commission'] && $row['aff_commission_paid'] )
				{
					$aff_commission_paid = date('M jS', strtotime($row['aff_commission_paid']));
				}
				elseif ( $row['aff_commission'] != 0 )
				{
					$aff_commission_paid = '-';
				}

				if ( $row['tier_commission'] && $row['tier_commission_paid'] )
				{
					$tier_commission_paid .= date('M jS', strtotime($row['aff_commission_paid']));

					if ( $tier_commission_paid != $aff_commission_paid )
					{
						$aff_commission_paid .= '<br />' . $tier_commission_paid;
					}
				}
				elseif ( $row['tier_commission'] != 0 )
				{
					$aff_commission_paid .= '<br />' . '-';
				}

				if ( !$aff_commission_paid || $aff_commission_paid == '-<br />-' )
				{
					$aff_commission_paid = '&nbsp;';
				}

				$total_commissions += $aff_commission;

				if ( $row['aff_commission_paid'] )
				{
					$total_commissions_paid += $row['aff_commission'];
				}

				if ( $row['tier_commission_paid'] )
				{
					$total_commissions_paid += $row['tier_commission'];
				}
			}
			elseif ( $row['is_owner'] )
			{
				$aff_commission = $row['aff_commission'];

				$aff_commission_paid = $row['aff_commission_paid']
						? date('M jS', strtotime($row['aff_commission_paid']))
						: '&nbsp;';

				$total_commissions += $aff_commission;

				if ( $row['aff_commission_paid'] )
				{
					$total_commissions_paid += $aff_commission;
				}
			}
			else
			{
				$aff_commission = $row['tier_commission'];

				$aff_commission_paid = $row['tier_commission_paid']
						? date('M jS', strtotime($row['tier_commission_paid']))
						: '&nbsp;';

				$total_commissions += $aff_commission;

				if ( $row['tier_commission_paid'] )
				{
					$total_commissions_paid += $aff_commission;
				}
			}

			echo '<tr align="center"' . ( $i%2 ? ' class="alt"' : '' )
					. ' style="cursor: pointer;"'
					. ' onclick="document.location = \'?date=' . date('Y', strtotime($_GET['date'])) . '\'"'
					. '>' . "\n"
				. ( ( !isset($prev_row) || $prev_row['campaign_key'] != $row['campaign_key'] || $prev_row['stats_date'] != $row['stats_date'] )
					? ( '<td rowspan="' . ( ( $row['num_orders'] > 1 ) ? $row['num_orders'] : 1 ) . '">'
						. date('M jS', strtotime($row['stats_date']))
						. '</td>'
						. '<td rowspan="' . ( ( $row['num_orders'] > 1 ) ? $row['num_orders'] : 1 ) . '">'
						. $row['num_visitors']
						. '</td>'
						. '<td rowspan="' . ( ( $row['num_orders'] > 1 ) ? $row['num_orders'] : 1 ) . '">'
						. $row['num_orders']
						. '</td>'
						)
					: ''
					)
				. ( isset($row['order_id'])
					? ( '<td align="right">'
						. number_format($row['order_amount'], 2)
						. '</td>'
						. '<td align="right">'
						. number_format($aff_commission, 2)
						. '</td>'
						. '<td>'
						. $aff_commission_paid
						. '</td>'
						)
					: ( '<td>&nbsp;</td>'
						. '<td>&nbsp;</td>'
						. '<td>&nbsp;</td>'
						)
					)
				. '</tr>' . "\n";

			$prev_row = $row;

			$row = db_get_row($res);

			if ( $row && $prev_row['stats_date'] != $row['stats_date'] )
			{
				$i++;
			}

			if ( !$row || ( $prev_row['campaign_key'] != $row['campaign_key'] ) )
			{
				echo '<tr align="center" class="ft">' . "\n"
					. '<td><b>' . date('M, Y', strtotime($prev_row['stats_date'])) . '</b></td>'
					. '<td><b>' . $total_visitors . '</b></td>'
					. '<td><b>' . $total_orders . '</b></td>'
					. '<td align="right"><b>'
						. ( $total_amount
							? number_format($total_amount, 2)
							: '&nbsp;'
							)
						. '</b></td>'
					. '<td align="right"><b>'
						. ( ( $total_amount )
							? number_format($total_commissions, 2)
							: '&nbsp;'
							)
						. '</b></td>'
					. '<td align="right"><b>'
						. ( ( $total_amount )
							? number_format($total_commissions_paid, 2)
							: '&nbsp;'
							)
						. '</b></td>'
					. '</tr>' . "\n";

				echo '</table>' . "\n";
			}
		} while ( $row );
	}
} # display_detailed_stats()
?>