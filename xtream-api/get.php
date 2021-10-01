<?php

/************************************************************************************/
/*																					*/
/*				get.php [ IPTV-Tools Xtream API ]								    */
/*																					*/
/*				Author	: Ernst Reidinga											*/
/*				Date 	: 27/06/2021												*/
/*				Version	: 1.0 (Compat XC V2.0)										*/
/*                                                                                  */
/************************************************************************************/

$show_errors = false;

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
        "type",
        "output",
        "streams"
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

function format_url ($stream, $type, $hls, $playlist) {
    $server = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://'.$_SERVER['HTTP_HOST'];
    $result = "";
    switch (intval($type)) {

        // Live
        case 1: 
            if ($hls === true) {
                $result = "{$server}/live/{$playlist['api_username']}/{$playlist['api_password']}/{$stream['id']}.m3u8";
            } else {
                $result = "{$server}/{$playlist['api_username']}/{$playlist['api_password']}/{$stream['id']}";
            }
            break;

        // Movie
        case 2: 
            $result = "{$server}/movie/{$playlist['api_username']}/{$playlist['api_password']}/{$stream['id']}.{$stream['source_container_extension']}";
            break;

        // Series
        case 3: 
            $result = "{$server}/series/{$playlist['api_username']}/{$playlist['api_password']}/{$stream['id']}.{$stream['source_container_extension']}";
            break;

    }
    return "{$result}\r\n";
}

function format_stream ($stream, $group, $plus) {
    $result = "#EXTINF:-1";
    switch (intval($group['group_type'])) {

        // Live stream
        case 1: 
            $tvg_name = $stream['stream_tvg_name'];
            break;

        // Movie
        case 2: 
            if (!empty($stream['movie_name']) && !empty($stream['movie_year'])) {
                $tvg_name = "{$stream['movie_name']} ({$stream['movie_year']})";
            } elseif (!empty($stream['movie_name'])) {
                $tvg_name = $stream['movie_name'];
            } else {
                $tvg_name = $stream['stream_tvg_name'];
            }
            break;

        // Series
        case 3:
            if (!empty($stream['serie_name']) && !empty($stream['serie_season']) && !empty($stream['serie_episode'])) {
                $tvg_name = "{$stream['serie_name']} - S{$stream['serie_season']}E{$stream['serie_episode']}";
            } else {
                $tvg_name = $stream['stream_tvg_name'];
            }
            break;

    }
    if ($plus === true) {
        $tags = [
            'tvg-id'       => $stream['stream_tvg_id'],
            'tvg-name'     => $tvg_name,
            'tvg-logo'     => $stream['stream_tvg_logo'],
            'group-title'  => $group['group_name'],
            'tvg-chno'     => $stream['stream_tvg_chno'],
            'channel-id'   => $stream['stream_tvg_chno'],
            'tvg-shift'    => $stream['stream_tvg_shift'],
            //'parent-code'  => !empty($group['group_parent_code']) ? $group['group_parent_code'] : !empty($stream['stream_parent_code']) ? $stream['stream_parent_code'] : "",
            'parent-code'  => (!empty($group['group_parent_code']) ? $group['group_parent_code'] : !empty($stream['stream_parent_code'])) ? $stream['stream_parent_code'] : "",
            'audio-track'  => $stream['stream_audio_track'],
            'aspect-ratio' => $stream['stream_aspect_ratio'],
            'radio'        => $stream['stream_radio'] == 1 ? "true" : ""
        ];
        foreach ($tags as $tag => $value) {
            if (!empty($value)) {
                $result .= " {$tag}=\"{$value}\"";
            }
        }
    }
    $result .= ",{$tvg_name}";
    return "{$result}\r\n";
}

function active_subscription ($subscription) {
    if ($subscription['expire'] != null) {
        $expire = new DateTime($subscription['expire']);
        $now    = new DateTime();
        return $expire < $now;
    }
    return true;
}

$parameters   = parse_parameters();
$playlist     = get_playlist($parameters['username'], $parameters['password']);
$subscription = get_subscription($playlist['user_id']);
$gmdate       = gmdate("D, d M Y H:00:00");

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
if (!empty($subscription) && !in_array($subscription['playlist_type'], [0, 2])) {
    http_response_code(401);
    header('Status: 401 Unauthorized');
    exit;
}

/************************************************************************************/
/*																					*/
/*				Head Request (Age of the playlist)									*/
/*																					*/
/************************************************************************************/
if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
    // Set header (M3U Playlist)
    header('Content-Type: application/mpegurl');
    // Set header (Last Modified date - simulate a local file)
    header("Last-Modified: {$gmdate} GMT");
    exit;
}
 
/************************************************************************************/
/*																					*/
/*				Route API Request		 											*/
/*																					*/
/************************************************************************************/
if (!empty($playlist) && boolval($playlist['enabled']) && !empty($subscription) && active_subscription($subscription)) {

    // Playlist type
    $plus = !empty($parameters['type']) && strtolower($parameters['type']) === 'm3u_plus';
    $hls  = !empty($parameters['output']) && strtolower($parameters['output']) === 'hls';

    // Set header (M3U Playlist)
    header('Content-Type: application/mpegurl');
    // Set header (Last Modified date - simulate a local file)
    header("Last-Modified: {$gmdate} GMT");
    // Set header (Download as file - attachment)
    header("Content-Disposition: attachment; filename=\"tv_channels_{$parameters['username']}.m3u\"");

    // Start M3U
    echo "#EXTM3U\r\n";

    // Groups
    if (empty($parameters['streams']) || strtoupper($parameters['streams']) === "ALL") {
        $groups = $sql->sql_select_array_query("SELECT * FROM `groups` WHERE `user_id` = '{$playlist['user_id']}' AND `playlist_id` = '{$playlist['id']}' AND `group_is_hidden` = 0 ORDER BY group_order ASC");
    } elseif (strtoupper($parameters['streams']) === "LIVE") {
        $groups = $sql->sql_select_array_query("SELECT * FROM `groups` WHERE `user_id` = '{$playlist['user_id']}' AND `playlist_id` = '{$playlist['id']}' AND `group_is_hidden` = 0 AND `group_type` = 1 ORDER BY group_order ASC");
    } elseif (strtoupper($parameters['streams']) === "MOVIES") {
        $groups = $sql->sql_select_array_query("SELECT * FROM `groups` WHERE `user_id` = '{$playlist['user_id']}' AND `playlist_id` = '{$playlist['id']}' AND `group_is_hidden` = 0 AND `group_type` = 2 ORDER BY group_order ASC");
    } elseif (strtoupper($parameters['streams']) === "SERIES") {
        $groups = $sql->sql_select_array_query("SELECT * FROM `groups` WHERE `user_id` = '{$playlist['user_id']}' AND `playlist_id` = '{$playlist['id']}' AND `group_is_hidden` = 0 AND `group_type` = 3 ORDER BY group_order ASC");
    }

    // Streams
    foreach ($groups as $group) {
        if ($group['group_type'] == 1) {
            $streams = $sql->sql_select_array_query("SELECT `id`, `user_id`, `playlist_id`, `group_id`, `stream_tvg_name`, `stream_tvg_id`, `stream_tvg_logo`, `stream_tvg_chno`, `stream_tvg_shift`, `stream_parent_code`, `stream_audio_track`, `stream_aspect_ratio`, `stream_radio`, `stream_order` FROM `live` WHERE `user_id` = '{$playlist['user_id']}' AND `playlist_id` = '{$playlist['id']}' AND `group_id` = '{$group['id']}' AND `stream_is_hidden` = 0 AND `sync_is_removed` = 0 ORDER BY stream_order ASC");
        } elseif ($group['group_type'] == 2) {
            $streams = $sql->sql_select_array_query("SELECT `id`, `user_id`, `playlist_id`, `group_id`, `stream_tvg_name`, `stream_tvg_id`, `stream_tvg_logo`, `stream_tvg_chno`, `stream_tvg_shift`, `stream_parent_code`, `stream_audio_track`, `stream_aspect_ratio`, `stream_radio`, `stream_order`, `source_container_extension`, `movie_name`, `movie_year` FROM `movie` WHERE `user_id` = '{$playlist['user_id']}' AND `playlist_id` = '{$playlist['id']}' AND `group_id` = '{$group['id']}' AND `stream_is_hidden` = 0 AND `sync_is_removed` = 0 ORDER BY stream_order ASC");
        } elseif ($group['group_type'] == 3) {
            $streams = $sql->sql_select_array_query("SELECT `id`, `user_id`, `playlist_id`, `group_id`, `stream_tvg_name`, `stream_tvg_id`, `stream_tvg_logo`, `stream_tvg_chno`, `stream_tvg_shift`, `stream_parent_code`, `stream_audio_track`, `stream_aspect_ratio`, `stream_radio`, `stream_order`, `source_container_extension`, `serie_name`, `serie_season`, `serie_episode` FROM `episodes` WHERE `user_id` = '{$playlist['user_id']}' AND playlist_id = '{$playlist['id']}' AND `group_id` = '{$group['id']}' AND `stream_is_hidden` = 0 AND `sync_is_removed` = 0 ORDER BY stream_order ASC");
        }//$stream, $type, plus, $group
        foreach ($streams as $stream) {
            echo format_stream($stream, $group, $plus);
            echo format_url($stream, $group['group_type'], $hls, $playlist);
        }
    }

} else {
    http_response_code(401);
    header('Status: 401 Unauthorized');
}
