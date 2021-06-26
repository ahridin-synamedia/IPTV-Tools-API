<?php

/**********************************************************************************/
/*																				  */
/*				const.php [ Constants ]		  									  */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 12/04/2021									  		  */
/*				Version	: 1.0												  	  */
/*																				  */
/**********************************************************************************/

/* Router Root */
define('ROOT_SEGMENT', 0);

/* SQL Database */
define('SQL', array(
	'app' => array(
		'server'	=> 'localhost',
		'username'	=> 'iptvtool_app',
		'password'	=> 'u8&o@K.fcGS^Z!;}Wy',
		'dbname'	=> 'iptvtool_app'
	)
));

/* Email accounts for API */
define('EMAIL', array(
	'noreply' => array(
		'server' 	=> '{localhost:993/imap/ssl/novalidate-cert}',
		'domain'	=> '@iptv-tools.com',
		'smtp' 		=> 'mail.iptv-tools.com',
		'name' 		=> 'IPTV-Tools',
		'password' 	=> '.6qB&6mHuJ)c'
	)
));

/* Server Key - used to sign the JWT */
define('SERVER_KEY', '11dd53eacc7b334c8d9bfc5d0a4b5f03');

/* TMDB API KEY */
define('TMDB_API_KEY', 'af538ec9bad9dba979b60eda62532e2d');