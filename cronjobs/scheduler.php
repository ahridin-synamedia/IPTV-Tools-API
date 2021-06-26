<?php

/**********************************************************************************/
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				For 	: ERDesigns.eu										  	  */
/*				Date 	: 31/05/2020    								  	  	  */
/*				Version	: 1.0.0.0												  */
/*																				  */
/*				Comment : Execute scheduled tasks (Cron Replacement)			  */
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

// Is schedule now?
function is_schedule_now ($schedule) {
	$dayofweek 	= (int)date('w');
	$month		= (int)date('m');
	$dayofmonth	= (int)date('d');
	$hour		= (int)date('H');
	$minute		= (int)date('i');
	// Is day of week set? and is the day a match
	if ($schedule['weekday'] !== '*' && (int)$schedule['weekday'] !== $dayofweek) {
		return false;
	} else 
	// Is month set? and is month a match
	if ($schedule['month'] !== '*' && (int)$schedule['month'] !== $month) {
		return false;
	} else
	// Is day of month set? and is the day a match
	if ($schedule['day'] !== '*' && (int)$schedule['day'] !== $dayofmonth) {
		return false;
	} else
	// Is the hour set? and is the hour a match
	if ($schedule['hour'] !== '*' && (int)$schedule['hour'] !== $hour) {
		return false;
	} else
	// Is the minute set? and is the minute a match
	if ($schedule['minute'] !== '*' && (int)$schedule['minute'] !== $minute) {
		return false;
	} else {
		return true;
	}
}

// If a error occurs trying to run, then update the schedule with status error.
function handle_schedule ($schedule) {
	global $sql;
	if (file_exists(strtok($schedule['command'],  ' ')) !== false) {
		$output = shell_exec('/usr/local/bin/php ' . $schedule['command'] . ' 2>&1');
	} else {
		$output = 'File not found! Filename: ' . $schedule['command'];
	}
    $sql->sql_update('scheduler', [
        'output' => $output
    ], [
        'id' => $schedule['id']
    ]);
}

// Get schedules - and check if a schedule need to be run now.
$schedules = $sql->sql_select_array_query("SELECT * FROM `scheduler` WHERE enabled = 1");
foreach ($schedules as $schedule) {
	if (is_schedule_now($schedule)) {
		handle_schedule($schedule);
	}
}