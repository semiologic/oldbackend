<?php
#
# db_connect()
#

function db_connect()
{
	if ( !defined('db_connected') )
	{
		$db_host = 'localhost';
		$db_name = 'semiologic';
		$db_user = 'semiologic';
		$db_pass = 'apjpfrjh';

		@pg_connect("host=$db_host dbname=$db_name user=$db_user password=$db_pass")
		or die('db_connect() failed');

		define('db_connected', true);
	}
} # db_connect()


#
# db_query()
#

function db_query($sql)
{
	db_connect();

	$res = pg_query($sql);

	if ( !$res )
	{
		die(pg_result_error());
	}

	return $res;
} # db_query()


#
# db_get_row()
#

function db_get_row($res)
{
	$row = pg_fetch_array($res, NULL, PGSQL_ASSOC);

	return $row;
} # db_get_row()


#
# db_get_var()
#

function db_get_var($res)
{
	if ( $row = pg_fetch_array($res, NULL, PGSQL_ASSOC) )
	{
		$var = current($row);
	}
	else
	{
		$var = null;
	}

	return $var;
} # db_get_var()


#
# db_escape()
#

function db_escape($str)
{
	db_connect();

	return pg_escape_string($str);
} # db_escape()


#
# db_num_rows()
#

function db_num_rows($res)
{
	return pg_num_rows($res);
} # db_num_rows()


#
# db_affected_rows()
#

function db_affected_rows($res)
{
	return pg_affected_rows($res);
} # db_affected_rows()
?>
