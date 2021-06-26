<?php

/**********************************************************************************/
/*																				  */
/*				schedulder_cron.php									    		  */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 31/05/2021    								  		  */
/*				Version	: 1.0													  */
/*																				  */
/**********************************************************************************/

include_once 'db_sql.php';
include_once 'const.php';
include_once 'toolbox.php';

$sql = new dbSQL(
	SQL['app']['server'],
	SQL['app']['username'],
	SQL['app']['password'],
	SQL['app']['dbname']
);

$arguments = arguments($argv);
$interval  = isset($arguments['interval']) ? $arguments['interval'] : 1;

/************************************************************************************/
/*																					*/
/*				Set Timezone to Amsterdam									 		*/
/*																					*/
/************************************************************************************/
date_default_timezone_set('Europe/Amsterdam');

/************************************************************************************/
/*																					*/
/*				XMLTV			                                   					*/
/*																					*/
/************************************************************************************/
$sources = $sql->sql_select_array_query("SELECT * FROM `xmltv_source` WHERE `sync_interval` = '{$interval}'");
$query   = "INSERT INTO `xmltv_sync` (`xmltv_source_id`) VALUES ";
foreach ($sources as $source) {
    $xmltv_source_id = $source['id'];
    $query .= "('{$xmltv_source_id}'),";
}
$sql->sql_query(rtrim($query, ','));

/************************************************************************************/
/*																					*/
/*				PLAYLIST		                                   					*/
/*																					*/
/************************************************************************************/
$sources = $sql->sql_select_array_query("SELECT * FROM `playlist` WHERE `sync_enabled` = 1 AND `sync_interval` = '{$interval}'");
$query   = "INSERT INTO `sync_playlist` (`user_id`, `playlist_id`) VALUES ";
foreach ($sources as $source) {
	$user_id     = $source['user_id'];
	$playlist_id = $source['id'];
	exec("/usr/local/bin/php /home/iptvtools/public_html/cron/sync_playlist.php --user={$user_id} --playlist={$playlist_id} > /dev/null &");
    $query .= "('{$user_id}', '{$playlist_id}'),";
}
$sql->sql_query(rtrim($query, ','));