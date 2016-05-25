<?php
require_once tmp_mc_path . '/utils/session.php';
require_user();

#
# update_campaigns()
#

function update_campaigns()
{
	if ( $_POST['campaign_key'] )
	{
		$res = db_query("
			SELECT start_campaign('" . $_SESSION['user_id'] . "', '" . $_POST['campaign_key'] . "');
			");

		if ( $campaign_key = db_get_var($res) )
		{
			add_message('New affiliate campaign started: ' . $campaign_key . '<br />'
				. 'Thank you for your ongoing support to semiologic.com!');
		}
		else
		{
			add_message('This campaign exists or its ID is invalid. Please choose a different campaign id.');
		}
	}

	do_redirect('campaigns.php');
} # update_campaigns()


#
# display_campaigns()
#

function display_campaigns()
{
	if ( !empty($_POST) )
	{
		update_campaigns();
	}

	include_once tmp_mc_path . '/header.php';

	display_messages();

	$res = db_query("
		SELECT	campaign_key
		FROM	campaigns
		WHERE	aff_id = '" . db_escape($_SESSION['user_id']) . "'
		ORDER BY campaign_key
		");

	echo '<form method="post" action="campaigns.php">' . "\n";

	echo '<h1>Affiliate Campaigns</h1>';

	echo '<p>Thanks for your interest in promoting Semiologic Pro. Please enter a valid <a href="http://www.paypal.com">paypal address</a> in your <a href="user.php">profile</a> to receive payments, and create one or more campaigns.</p>';

	echo '<h2>'
		. 'Affiliate Campaigns'
		. '</h2>' . "\n";

	while ( $campaign = db_get_row($res) )
	{
		echo '<pre>http://www.getsemiologic.com?aff=<strong>' . $campaign['campaign_key'] . '</strong></pre>' . "\n";
	}

	echo '<p class="field">'
		. 'New Campaign:<br />' . "\n"
		. '<input type="text"'
			. ' name="campaign_key"'
			. ' value=""'
			. ' />'
		. '</p>' . "\n";

	echo '<p class="submit">'
		. '<button type="submit">Save</button>'
		. '</p>' . "\n";


	$res = db_query("
			SELECT	*
			FROM	products
			WHERE	product_is_active
			ORDER BY product_name
		");

	echo '<h2>'
		. 'Commission Details'
		. '</h2>';

	echo '<table class="datagrid">';

	echo '<tr align="center" class="hd">'
		. '<th>Product</th>'
		. '<th width="80">Price</th>'
		. '<th width="80">Commission</th>'
		. '<th width="80">Gold Commission</th>'
		. '</tr>';

	$i = 0;

	while ( $row = db_get_row($res) )
	{
		echo '<tr align="center"' . ( $i%2 ? ' class="alt"' : '' ) . '>'
			. '<td align="left">' . $row['product_name'] . '</td>'
			. '<td align="right">' . number_format($row['product_price'], 2) . '</td>'
			. '<td>' . $row['product_commission'] . '%</td>'
			. '<td>' . $row['product_gold_commission'] . '%</td>'
			. '</tr>';

		$i++;
	}

	echo '</table>';

	echo '<dl>'
		. '<dt>Commission</dt>'
		. '<dd>You get the standard commission when you generate a sale.'
		. '<dt>Gold Commission</dt>'
		. '<dd>You get the gold commission instead when the previous sale you generated is no older than a week.'
		. '</dl>';

	echo '</form>' . "\n";

	include_once tmp_mc_path . '/footer.php';
} # display_campaigns()
?>