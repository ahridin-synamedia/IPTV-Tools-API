<?php

/**********************************************************************************/
/*																				  */
/*				xmltv_delete.php (Cron Executed Script)							  */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 21/05/2021    								  		  */
/*				Version	: 1.0													  */
/*																				  */
/**********************************************************************************/

include_once 'db_sql.php';
include_once 'const.php';

$sql = $sql = new dbSQL(
	SQL['app']['server'],
	SQL['app']['username'],
	SQL['app']['password'],
	SQL['app']['dbname']
);

/************************************************************************************/
/*																					*/
/*				Set Timezone to Amsterdam									 		*/
/*																					*/
/************************************************************************************/
date_default_timezone_set('Europe/Amsterdam');

/************************************************************************************/
/*																					*/
/*				MAIN FUNCTIONS                                   					*/
/*																					*/
/************************************************************************************/
$sql->sql_query("DELETE FROM xmltv_programmes where `stop` < NOW() - interval 14 DAY");