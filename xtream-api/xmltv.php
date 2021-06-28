<?php

/************************************************************************************/
/*																					*/
/*				xmltv.php [ IPTV-Tools Xtream API ]								    */
/*																					*/
/*				Author	: Ernst Reidinga											*/
/*				Date 	: 27/06/2021												*/
/*				Version	: 1.0 (Compat XC V2.0)										*/
/*                                                                                  */
/************************************************************************************/

$show_errors = false;
$info_name   = "IPTV-Tools";
$info_url    = "https://iptv-tools.com";

set_time_limit(0);
ini_set('memory_limit', -1);

/************************************************************************************/
/*																					*/
/*				Show errors?            									 		*/
/*																					*/
/************************************************************************************/
if ($show_errors) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

/************************************************************************************/
/*																					*/
/*				Set Timezone to Amsterdam									 		*/
/*																					*/
/************************************************************************************/
date_default_timezone_set('Europe/Amsterdam');

/************************************************************************************/
/*																					*/
/*				Site Root Directory											 		*/
/*																					*/
/************************************************************************************/
define('SITE_ROOT', __DIR__);

/************************************************************************************/
/*																					*/
/*				Include classes												 		*/
/*																					*/
/************************************************************************************/
include_once SITE_ROOT . '/common/const.php';
include_once SITE_ROOT . '/common/db_sql.php';

/************************************************************************************/
/*																					*/
/*				Create SQL Connection											 	*/
/*																					*/
/************************************************************************************/
$sql = new dbSQL(
	SQL['app']['server'],
	SQL['app']['username'],
	SQL['app']['password'],
	SQL['app']['dbname']
);

/************************************************************************************/
/*																					*/
/*				Functions                   									 	*/
/*																					*/
/************************************************************************************/
function ip_address () {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function parse_parameters() {
    $parameters = [
        "username",
        "password",
        "prev_days",
        "next_days"
    ];
    $output = [];
    foreach ($parameters as &$param) {
        $output[$param] = fetch_parameter($param);
    }
    return $output;
}

function fetch_parameter($param) {
    return isset($_REQUEST[$param]) ? $_REQUEST[$param] : "";
}

function get_playlist ($username, $password) {
    global $sql;
    $result = $sql->sql_select_array_query("SELECT *, UNIX_TIMESTAMP(created_at) as created_at FROM `playlist` WHERE BINARY `api_username` = '{$username}' AND BINARY `api_password` = '{$password}'");
    return count($result) === 1 ? $result[0] : null;
}

function get_subscription ($user_id) {
    global $sql;
    $result = $sql->sql_select_array_query("SELECT *, UNIX_TIMESTAMP(end_date) as expire FROM `subscription` WHERE `user_id` = '{$user_id}'");
    return count($result) === 1 ? $result[0] : null;
}

function implode_epg_ids ($epg_ids) {
    $result = "";
    foreach ($epg_ids as $epg_id) {
        $result .= "'{$epg_id}',";
    }
    return rtrim($result, ',');
}

$parameters   = parse_parameters();
$playlist     = get_playlist($parameters['username'], $parameters['password']);
$subscription = get_subscription($playlist['user_id']);
$gmdate       = gmdate("D, d M Y 00:00:00");

/************************************************************************************/
/*																					*/
/*				IP-Security Check		 											*/
/*																					*/
/************************************************************************************/
if (isset($playlist['ip_protection']) && boolval($playlist['ip_protection']) === true && isset($playlist['ip_allowed']) && !empty($playlist['ip_allowed'])) {
    if (!in_array(ip_address(), $playlist['ip_allowed'])) {
        http_response_code(401);
        header('Status: 401 Unauthorized');
        exit;
    }
}

/************************************************************************************/
/*																					*/
/*				Playlist Type Check		 											*/
/*																					*/
/************************************************************************************/
if (!empty($subscription) && !in_array($subscription['playlist_type'], [0, 1, 2, 3])) {
    http_response_code(401);
    header('Status: 401 Unauthorized');
    exit;
}

/************************************************************************************/
/*																					*/
/*				Head Request (Age of the XMLTV)										*/
/*																					*/
/************************************************************************************/
if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
    // Set header (XML Content Type)
    header('Content-Type: application/xml; charset=utf-8');
    // Set header (Last Modified date - simulate a local file)
    header("Last-Modified: {$gmdate} GMT");
    exit;
}
 
/************************************************************************************/
/*																					*/
/*				Route API Request		 											*/
/*																					*/
/************************************************************************************/
if (!empty($playlist) && boolval($playlist['enabled']) && !empty($subscription)) {

    // EPG Timeshift
    $timeshift = intval($playlist['epg_offset']) * 3600;

    // EPG Duration
    $prev_days = empty($parameters['prev_days']) ? 1 : abs(intval($parameters['prev_days']));
    $next_days = empty($parameters['next_days']) ? 1 : abs(intval($parameters['next_days']));
    
    // Set header (XML Content Type)
    header('Content-Type: application/xml; charset=utf-8');
    // Set header (Last Modified date - simulate a local file)
    header("Last-Modified: {$gmdate} GMT");

    // XMLTV
    echo "<?xml version=\"1.0\" encoding=\"utf-8\" ?><!DOCTYPE tv SYSTEM \"xmltv.dtd\">";
    echo "<tv generator-info-name=\"{$info_name}\" generator-info-url=\"{$info_url}\">";

    // TV Channels
    $epg_ids  = [];
    $channels = $sql->sql_select_array_query("SELECT * FROM `live` WHERE user_id = '{$playlist['user_id']}' AND playlist_id = '{$playlist['id']}' AND stream_tvg_id <> '' AND stream_tvg_id IS NOT NULL AND stream_tvg_id IN (SELECT tvg_id FROM `xmltv_stations`) AND `stream_is_hidden` = 0");
    foreach ($channels as $channel) {
        
        // Add EPG ID to array for programmes
        $epg_ids[] = $channel['stream_tvg_id'];
        
        // EPG ID
        $tvg_id = htmlspecialchars($channel['stream_tvg_id'], ENT_XML1 | ENT_QUOTES | ENT_DISALLOWED, 'UTF-8');
        echo "<channel id=\"{$tvg_id}\">";
        
        // Channel Name
        $tvg_name = htmlspecialchars($channel['stream_tvg_name'], ENT_XML1 | ENT_QUOTES | ENT_DISALLOWED, 'UTF-8');
        echo "<display-name>{$tvg_name}</display-name>";
        
        // Channel Logo
        if (!empty($channel['stream_tvg_logo'])) {
            $tvg_logo = htmlspecialchars($channel['stream_tvg_logo'], ENT_XML1 | ENT_QUOTES | ENT_DISALLOWED, 'UTF-8');
            echo "<icon src=\"{$tvg_logo}\" />";
        }

        // Close Channel
        echo "</channel>";
    }
    
    // Programmes
    $epg_ids = implode_epg_ids(array_unique($epg_ids));
    $start   = date('Y-m-d H:i:00', strtotime("-{$prev_days} day"));
    $stop    = date('Y-m-d H:i:00', strtotime("+{$next_days} day"));
    $programmes = $sql->sql_select_array_query("SELECT tvg_id, title, subtitle, description, season, episode, year, DATE_ADD(p.start, INTERVAL p.offset + {$timeshift} second) as 'start', DATE_ADD(p.start, INTERVAL p.offset + {$timeshift} second) as 'stop' FROM `xmltv_programmes` p WHERE tvg_id IN ({$epg_ids}) AND start BETWEEN '{$start}' AND '{$stop}' ORDER BY tvg_id, start ASC");
    foreach ($programmes as $programme) {

        // Start programme
        $tvg_id   = htmlspecialchars($programme['tvg_id'], ENT_XML1 | ENT_QUOTES | ENT_DISALLOWED, 'UTF-8');
        $start_dt = new DateTime($programme['start']);
        $start    = $start_dt->format('YmdHis +0000');
        $stop_dt  = new DateTime($programme['stop']);
        $stop     = $stop_dt->format('YmdHis +0000');
        echo "<programme start=\"{$start}\" stop=\"{$stop}\" channel=\"{$tvg_id}\" >";

        // Title
        $title = htmlspecialchars($programme['title'], ENT_XML1 | ENT_QUOTES | ENT_DISALLOWED, 'UTF-8');
        echo "<title>{$title}</title>";

        // Subtitle
        if (!empty($programme['subtitle'])) {
            $subtitle = htmlspecialchars($programme['subtitle'], ENT_XML1 | ENT_QUOTES | ENT_DISALLOWED, 'UTF-8');
            echo "<sub-title>{$subtitle}</sub-title>";
        }

        // Description
        if (!empty($programme['description'])) {
            $description = htmlspecialchars($programme['description'], ENT_XML1 | ENT_QUOTES | ENT_DISALLOWED, 'UTF-8');
            echo "<desc>{$description}</desc>";
        }

        // Season / Episode
        if (!empty($programme['season']) && $programme['season'] > 0 || !empty($programme['episode']) && $programme['episode'] > 0) {
            
            // On Screen
            if (!empty($programme['season']) && !empty($programme['episode'])) {
                echo "<episode-num system=\"onscreen\">S{$programme['season']}E{$programme['episode']}</episode-num>";
            } elseif (!empty($programme['season'])) {
                echo "<episode-num system=\"onscreen\">S{$programme['season']}</episode-num>";
            } elseif (!empty($programme['episode'])) {
                echo "<episode-num system=\"onscreen\">E{$programme['episode']}</episode-num>";
            }

            // XMLTV NS
            $season  = intval($programme['season']) -1;
            $episode = intval($programme['episode']) -1;
            if (!empty($programme['season']) && !empty($programme['episode'])) {
                echo "<episode-num system=\"xmltv_ns\">{$season}.{$episode}</episode-num>";
            } elseif (!empty($programme['season'])) {
                echo "<episode-num system=\"xmltv_ns\">{$season}.</episode-num>";
            } elseif (!empty($programme['episode'])) {
                echo "<episode-num system=\"xmltv_ns\">.{$episode}</episode-num>";
            }            

        }

        // Date
        if (!empty($programme['year'])) {
            echo "<date>{$programme['year']}</date>";
        }

        // Close programme
        echo "</programme>";

    }

    // Close XMLTV
    echo "</tv>";
} else {
    http_response_code(401);
    header('Status: 401 Unauthorized');
}
