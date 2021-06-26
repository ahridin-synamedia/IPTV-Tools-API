<?php

/**********************************************************************************/
/*																				  */
/*				sync_playlist_cron.php								    		  */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 11/06/2021    								  		  */
/*				Version	: 1.0													  */
/*																				  */
/**********************************************************************************/

include_once 'db_sql.php';
include_once 'const.php';

$sql = new dbSQL(
	SQL['app']['server'],
	SQL['app']['username'],
	SQL['app']['password'],
	SQL['app']['dbname']
);

$waiting = $sql->sql_select_array_query("SELECT * FROM `sync_playlist` WHERE `active` = 0");
foreach ($waiting as $source) {
	$user_id     = $source['user_id'];
	$playlist_id = $source['playlist_id'];
	exec("/usr/local/bin/php /home/iptvtools/public_html/cron/sync_playlist.php --user={$user_id} --playlist={$playlist_id} > /dev/null &");
}