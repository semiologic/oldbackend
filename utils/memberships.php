<?php
require_once tmp_mc_path . '/utils/session.php';
require_user();


#
# display_memberships()
#

function display_memberships()
{
	include_once tmp_mc_path . '/header.php';


	echo '<h1>'
		. 'Memberships'
		. '</h1>';


	$res = db_query("
			SELECT	memberships.*
			FROM	memberships
			WHERE	user_id = '" . db_escape($_SESSION['user_id']) . "'
			ORDER BY profile_name
		");

	if ( $row = db_get_row($res) )
	{
		echo '<h2>'
			. 'Current Memberships'
			. '</h2>';

		echo '<table class="datagrid">' . "\n";

		echo '<tr align="center" class="hd">' . "\n"
			. '<th width="50%">'
			. 'Membership'
			. '</th>'
			. '<th width="50%">'
			. 'Expires'
			. '</th>'
			. '</tr>' . "\n";

		$i = 0;

		do
		{
			echo '<tr align="center"' . ( $i%2 ? ' class="alt"' : '' ) . '>' . "\n"
				. '<td>'
				. $row['profile_name']
				. '</td>'
				. '<td>'
				. ( $row['membership_expires']
					? ( ( ( strtotime($row['membership_expires']) < strtotime("+3 months") )
							? ( '<span style="color: firebrick;">' . date('M dS, Y', strtotime($row['membership_expires'])) . '</span>' )
							: date('M dS, Y', strtotime($row['membership_expires']))
							)
						. ( ( strtotime($row['membership_expires']) < time() )
							? ' (Expired)'
							: ''
							)
						)
					: 'Never'
					)
				. '</td>'
				. '</tr>' . "\n";

			$i++;
		} while ( $row = db_get_row($res) );

		echo '</table>' . "\n";
	}


	$res = db_query("
			SELECT	membership_renewals.*
			FROM	membership_renewals
			WHERE	user_id = '" . db_escape($_SESSION['user_id']) . "'
			ORDER BY product_key, profile_name
		");


	if ( $row = db_get_row($res) )
	{
		echo '<h2>'
			. 'Membership Renewals'
			. '</h2>';

		echo '<table class="datagrid">' . "\n";

		echo '<tr align="center" class="hd">' . "\n"
			. '<th>'
			. 'Membership'
			. '</th>'
			. '<th>'
			. 'Duration'
			. '</th>'
			. '</tr>' . "\n";

		$i = 0;

		do
		{
			if ( !isset($prev_row) || $prev_row['product_id'] != $row['product_id'] )
			{
			}

			echo '<tr align="center"' . ( $i%2 ? ' class="alt"' : '' ) . '>' . "\n"
				. '<td align="left">'
				. $row['profile_name']
				. '</td>'
				. '<td>'
				. $row['membership_duration']
				. '</td>'
				. '</tr>' . "\n";

			$prev_row = $row;

			$row = db_get_row($res);

			if ( !$row || $prev_row['product_id'] != $row['product_id'] )
			{
				echo '<tr align="center" class="ft">' . "\n"
					. '<td align="left">'
					. '<b>' . $prev_row['product_name'] . '</b>'
					. '</td>'
					. '<td align="right"><b>'
					. '<a href="order.php?product=' . $prev_row['product_key'] . '">'
					. number_format($prev_row['product_price'], 2)
					. '</a>'
					. '</b></td>'
					. '</tr>' . "\n";

				if ( $row )
				{
					echo '<tr class="ft" colspan="3" height="2">'
						. '<td></td>'
						. '</tr>' . "\n";
				}

				$i++;
			}
		} while ( $row );

		echo '</table>' . "\n";
	}

	include_once tmp_mc_path . '/footer.php';
} # display_memberships()
?>