<?php
#
# do_redirect()
#

function do_redirect($location)
{
	if ( strpos($_SERVER['SERVER_SOFTWARE'], 'IIS') !== false )
	{
		header("Refresh: 0;url=$location");
	}
	else
	{
		header("Location: $location");
	}

	die;
} # do_redirect()


#
# send_email()
#

function send_email($to, $title, $message, $from)
{
	if ( $_SERVER['HTTP_HOST'] != 'localhost' )
	{
		$headers = "From: $from\r\n"
			. "Reply-To: <$from>\r\n"
			. "Return-Path: <$from>\r\n"
			. "X-Sender: $from\r\n"
			. "X-Mailer: PHP/" . phpversion();

		$headers = "From: $from";

		return mail(
			$to,
			$title,
			$message,
			$headers
	//		,
	//		"-f $from"
			);
	}
	else
	{
		add_message('<pre>'
			. 'Email sent from ' . $from . ' to ' . $to . "\n\n"
			. $title . "\n\n"
			. $message
			. '<pre>'
			);
	}
} # send_email()


#
# add_message()
#

function add_message($msg)
{
	if ( $msg )
	{
		$_SESSION['message'][] = $msg;
	}
} # add_message()


#
# display_messages()
#

function display_messages()
{
	if ( isset($_SESSION['message']) )
	{
		echo '<div id="messages">'
			. '<ul>';
		foreach ( $_SESSION['message'] as $message )
		{
			echo '<li>' . $message . '</li>';
		}

		echo '</div>';

		unset($_SESSION['message']);
	}
} # display_messages()


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
?>