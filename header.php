<html>
<head>
	<title>Semiologic Back-End</title>
<style>

body
{
	text-align: center;
}

div
{
	text-align: left;
}

body,
table
{
	font-family: Trebuchet MS, Tahoma, Helvetica, Sans-Serif;
	font-size: medium;
	color: black;
	background-color: white;
}

h1
{
	font-size: large;
	margin-top: 1em;
}

h2
{
	font-size: medium;
	margin-top: 1em;
}

pre,
code
{
	font-family: Courier New, Courier, Monospace;
	font-size: medium;
}

dt
{
	font-style: italic;
}

#wrapper
{
	width: 750px;
	border: solid 1px black;
	margin: 20px auto;
}

#content
{
	width: 550px;
	float: left;
}

#content div.pad
{
	padding: 0px 20px;
}

#sidebar
{
	width: 198px;
	float: right;
}

#sidebar div.pad
{
	padding: 0px 10px;
}

#footer
{
	text-align: center;
}

#footer div.pad
{
	padding: 0px 10px;
	text-align: center;
}

table
{
	width: 510px;
	border: none;
	border-collapse: collapse;
	margin-bottom: 10px;
}

table.datagrid
{
	font-size: small;
}

table th,
table td
{
	border: solid 1px gainsboro;
	padding: 2px 3px;
}

table tr.hd
{
	background-color: lavender;
}

table tr.ft
{
	background-color: #ffffcc;
}

table tr.alt
{
	background-color: ghostwhite;
}


a
{
	color: blue;
}

a:hover
{
	color: firebrick;
}

form
{
	margin: 0px;
	padding: 0px;
}

p.field input
{
	width: 100%;
	margin: 1px 0px;
}

p.submit
{
	text-align: right;
}

#messages
{
	margin: 10px 0px;
	padding: 10px;
	background-color: ghostwhite;
	border: solid 1px lightsteelblue;
}

#messages ul,
#messages li
{
	margin-left: 0px;
	padding-left: 0px;
	list-style: none;
}

#login
{
	width: 360px;
	margin: 20px auto 0px auto;
	border: solid 1px black;
}

#login div.pad
{
	padding: 0px 20px;
}

#sidebar ul,
#sidebar li
{
	margin-left: 0px;
	padding-left: 0px;
	list-style: none;
}

</style>
</head>
<body>
<?php
if ( $GLOBALS['cmd'] != 'login' ) :
?>
<div id="wrapper">
<div id="content">
<div class="pad">
<?php
endif;
?>