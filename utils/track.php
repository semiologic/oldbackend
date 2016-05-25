<?php
require_once tmp_mc_path . '/utils/utils.php';
require_once tmp_mc_path . '/utils/db.php';


#
# set_ref_cookie()
#

function set_ref_cookie($ref_id = '')
{
	if ( $_SERVER['HTTP_HOST'] == 'localhost' )
	{
		setcookie(
			'ref_id',
			$ref_id,
			time() + 60 * 60 * 24 * 91,
			'/'
			);
	}
	else
	{
		foreach ( array('semiologic.com', '.semiologic.com') as $domain )
		{
			$success = setcookie(
				'ref_id',
				$ref_id,
				time() + 60 * 60 * 24 * 91,
				'/',
				$domain
				);
		}
	}

	$_COOKIE['ref_id'] = $ref_id;
	$_COOKIE['ref_lock'] = 1;
} # set_ref_cookie()


#
# track_ref()
#

function track_ref()
{
	foreach ( array(
		'ref',
		'aff',
		'hop',
		'via',
		'thank',
		'track'
		) as $var )
	{
		if ( isset($_GET[$var]) )
		{
			$res = db_query("
				SELECT track_campaign('" . db_escape($_GET[$var]) . "'::varchar);
				");

			$ref_id = db_get_var($res);

			if ( $ref_id )
			{
				set_ref_cookie($ref_id);
			}

			break;
		}
	}

	if ( $_GET['redirect'] )
	{
		do_redirect($_GET['redirect']);
	}
} # track_ref()


#
# redirect_track()
#

function redirect_track()
{
	foreach ( array(
			'ref',
			'aff',
			'hop',
			'via',
			'thank',
			'track'
			) as $var )
	{
		if ( isset($_GET[$var]) )
		{
			if ( $_SERVER['HTTP_HOST'] != 'localhost' )
			{
				$location = 'http://oldbackend.semiologic.com/track.php';
			}
			else
			{
				$location = 'http://localhost/members/track.php';
			}

			$script = preg_replace("/index\.php$/", '', $_SERVER['SCRIPT_NAME']);
			$args = preg_replace("/(^|&)(ref|aff|hop|via|thank|track)=[^&]*/i", '', $_SERVER['QUERY_STRING']);

			$redirect = 'http://' . $_SERVER['HTTP_HOST']
				. $script
				. ( $args
					? '?' . $args
					: ''
					);

			do_redirect($location
				. '?ref=' . $_GET[$var]
				. '&redirect=' . urlencode($redirect)
				);

			break;
		}
	}

	if ( isset($_COOKIE['sem_aff_d8749f2d269f1b150a8a3b72be531636']) )
	{
			setcookie(
				'sem_aff_d8749f2d269f1b150a8a3b72be531636',
				false,
				time() - 60 * 60,
				'/'
				);

			if ( $_SERVER['HTTP_HOST'] != 'localhost' )
			{
				$location = 'http://oldbackend.semiologic.com/track.php';
			}
			else
			{
				$location = 'http://localhost/members/track.php';
			}

			$script = preg_replace("/index\.php$/", $_SERVER['SCRIPT_NAME']);
			$args = preg_replace("/(^|&)(ref|aff|hop|via|thank|track)=[^&]*/i", '', $_SERVER['QUERY_STRING']);

			$redirect = 'http://' . $_SERVER['HTTP_HOST']
				. $script
				. ( $args
					? '?' . $args
					: ''
					);

			do_redirect($location
				. '?ref=' . $_COOKIE['sem_aff_d8749f2d269f1b150a8a3b72be531636']
				. '&redirect=' . urlencode($redirect)
				);
	}
} # redirect_track()
?>
