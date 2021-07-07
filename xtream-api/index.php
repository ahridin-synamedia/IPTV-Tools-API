<?php

/************************************************************************************/
/*																					*/
/*				index.php [ IPTV-Tools Xtream API ]								    */
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
include_once SITE_ROOT . '/common/router.php';
include_once SITE_ROOT . '/common/db_sql.php';

/************************************************************************************/
/*																					*/
/*				Create Router               									 	*/
/*																					*/
/************************************************************************************/
$router  = new routeUrl('/', ROOT_SEGMENT);
$segment = 1;

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

function get_stream ($stream_id, $stream_type, $playlist) {
    global $sql;
    switch ($stream_type) {

        // Live stream
        case 1:
        case 4:
        case 5:
            $stream = $sql->sql_select_array_query("SELECT `source_stream_id`, `source_stream_url`, `stream_is_custom` FROM `live` WHERE `user_id` = '{$playlist['user_id']}' AND `playlist_id` = '{$playlist['id']}' AND `id` = '{$stream_id}'");
            break;

        // Movie
        case 2:
            $stream = $sql->sql_select_array_query("SELECT `source_stream_id`, `source_stream_url`, `source_container_extension`, `stream_is_custom` FROM `movie` WHERE `user_id` = '{$playlist['user_id']}' AND `playlist_id` = '{$playlist['id']}' AND `id` = '{$stream_id}'");
            break;

        // Series
        case 3:
            $stream = $sql->sql_select_array_query("SELECT `source_stream_id`, `source_stream_url`, `source_container_extension`, `stream_is_custom` FROM `episodes` WHERE `user_id` = '{$playlist['user_id']}' AND `playlist_id` = '{$playlist['id']}' AND `id` = '{$stream_id}'");
            break;
    }
    return count($stream) === 1 ? $stream[0] : null;
}

function needs_resolving ($url) {
    return preg_match("/youtu\.?be/", $url) || preg_match("/vimeo\.com/i", $url) || preg_match("/dailymotion\.com/i", $url) || preg_match("/xhamster\.com\/videos/i", $url) || preg_match("/ted\.com\/talks/i", $url);
}

function resolve_and_play ($url) {
    header("Content-Disposition: attachment; filename=\"video.mp4\"" );
    header("Content-Type: application/octet-stream");
    passthru("/usr/local/bin/youtube-dl $url -o -");
}

function proxy_and_play ($url) {
    ob_start();
    if(isset($_SERVER['HTTP_RANGE'])) {
        $opts['http']['header'] = "Range: ".$_SERVER['HTTP_RANGE'];
    }
    $opts['http']['method'] = "HEAD";
    $conh = stream_context_create($opts);
    $opts['http']['method'] = "GET";
    $cong = stream_context_create($opts);
    $out[] = file_get_contents($url, false, $conh);
    $out[] = $http_response_header;
    ob_end_clean();
    array_map("header", $http_response_header);
    readfile($url, false, $cong);
}

function redirect_and_play ($url) {
    header("Location: $url");
}

/************************************************************************************/
/*																					*/
/*				Route API Request		 											*/
/*																					*/
/************************************************************************************/
if ($router->request_method() === 'GET') {
    switch ($router->segment($segment, true)) {

        // Movie
        case 'MOVIE':
            $playlist  = get_playlist($router->segment(++$segment), $router->segment(++$segment));
            $stream_id = pathinfo($router->segment(++$segment), PATHINFO_FILENAME);
            $stream_type = 2;
            break;

        // Series
        case 'SERIES':
            $playlist  = get_playlist($router->segment(++$segment), $router->segment(++$segment));
            $stream_id = pathinfo($router->segment(++$segment), PATHINFO_FILENAME);
            $stream_type = 3;
            break;

        // Live (HLS / MPEG-TS)
        case 'LIVE':
            $playlist  = get_playlist($router->segment(++$segment), $router->segment(++$segment));
            $stream_id = pathinfo($router->segment(++$segment), PATHINFO_FILENAME);
            $extension = pathinfo($router->segment($segment), PATHINFO_EXTENSION);
            if (strtolower($extension) == 'ts') {
                $stream_type = 1;
            } else {
                $stream_type = 4;
            }
            break;

        // Catch-Up
        case 'TIMESHIFT':
            $playlist   = get_playlist($router->segment(++$segment), $router->segment(++$segment));
            $duration   = $router->segment(++$segment);
            $start_date = $router->segment(++$segment);
            $stream_id  = pathinfo($router->segment(++$segment), PATHINFO_FILENAME);
            $stream_type = 5;
            break;

        // Live (MPEG-TS)
        default:
            $playlist  = get_playlist($router->segment($segment), $router->segment(++$segment));
            $stream_id = pathinfo($router->segment(++$segment), PATHINFO_FILENAME);
            $stream_type = 1;
            break;

    }
} else {
    http_response_code(401);
    header('Status: 401 Unauthorized');
    exit;
}

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
/*				Get stream and stream URL											*/
/*																					*/
/************************************************************************************/
$stream = get_stream($stream_id, $stream_type, $playlist);
if (!empty($stream) && !is_null($stream)) {

    // Get URL for video/stream
    if ($stream['stream_is_custom']) {
        $stream_url = $stream['source_stream_url'];
    } else {
        $port = !empty($playlist['source_port']) ? $playlist['source_port'] : '80';
        switch ($stream_type) {
            
            // Live (MPEG-TS)
            case 1:
                $stream_url = "http://{$playlist['source_host']}:{$port}/{$playlist['source_username']}/{$playlist['source_password']}/{$stream['source_stream_id']}";
                break;

            // Live (HLS)
            case 4:
                $stream_url = "http://{$playlist['source_host']}:{$port}/live/{$playlist['source_username']}/{$playlist['source_password']}/{$stream['source_stream_id']}.m3u8";
                break;

            // Movie
            case 2:
                $stream_url = "http://{$playlist['source_host']}:{$port}/movie/{$playlist['source_username']}/{$playlist['source_password']}/{$stream['source_stream_id']}.{$stream['source_container_extension']}";
                break;

            // Serie
            case 3:
                $stream_url = "http://{$playlist['source_host']}:{$port}/series/{$playlist['source_username']}/{$playlist['source_password']}/{$stream['source_stream_id']}.{$stream['source_container_extension']}";
                break;

            // Catch-Up
            case 5:
                $stream_url = "http://{$playlist['source_host']}:{$port}/timeshift/{$playlist['source_username']}/{$playlist['source_password']}/{$duration}/{$start_date}/{$stream['source_stream_id']}.ts";
                break; 

        }
    }

    // Resolve and play / Redirect and play
    if ($stream['stream_is_custom']) {
        if (needs_resolving($stream_url)) {
            resolve_and_play($stream_url);
        } else {
            redirect_and_play($stream_url);
        }
    } else {
        redirect_and_play($stream_url);
    }

} else {
    http_response_code(404);
    header('Status: 404 Not Found');
    exit;
}

