<?php

/************************************************************************************/
/*																					*/
/*				panel_api.php [ IPTV-Tools Xtream API ]							    */
/*																					*/
/*				Author	: Ernst Reidinga											*/
/*				Date 	: 27/06/2021												*/
/*				Version	: 1.0 (Compat XC V2.0)										*/
/*                                                                                  */
/************************************************************************************/

$show_errors = false;

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
/*				Variables                   									 	*/
/*																					*/
/************************************************************************************/
$tmdb_prefix = 'http://tmdb-img.iptv-tools.com';

$ports = [
    'http'  => 80,
    'https' => 443,
    'rtmp'  => 80
];

$stream_type = [
    1 => 'Live',
    2 => 'Movie',
    3 => 'Series',
    4 => 'Radio'
];

$actions = [
    200 => 'get_epg'
];

$output_formats = [
    1 => ['ts'],
    2 => ['m3u8']
];

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

function server_url() {
    return (isset($_SERVER['HTTPS']) ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
}

function fetch_parameter($param) {
    return isset($_REQUEST[$param]) ? $_REQUEST[$param] : "";
}

function parse_parameters() {
    $parameters = [
        "username",
        "password",
        "action",
        "stream_id",
        "from_now"
    ];
    $output = [];
    foreach ($parameters as &$param) {
        $output[$param] = fetch_parameter($param);
    }
    return $output;
}

function echo_response ($response) {
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : server_url();
    header("Access-Control-Allow-Origin: {$origin}");
    header('Access-Control-Allow-Credentials: true');
    header('Content-Type: application/json');
    echo json_encode($response, JSON_PARTIAL_OUTPUT_ON_ERROR);
}

function get_active_connections ($playlist) {
    return 0;
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

function get_status ($playlist, $subscription) {
    if (!boolval($playlist['enabled'])) {
        return 'Disabled';
    }
    if (!is_null($subscription['expire']) && time() > $subscription['expire']) {
        return 'Expired';
    }
    if (!boolval($subscription['enabled'])) {
        return 'Banned';
    }
    return 'Active';
}

function get_categories ($playlist, $group_type) {
    global $sql;
    return $sql->sql_select_array_query("SELECT id AS category_id, group_name AS category_name, '1' AS parent_id FROM `groups` WHERE `user_id` = '{$playlist['user_id']}' AND `playlist_id` = '{$playlist['id']}' AND `group_type` = '{$group_type}' AND `group_is_hidden` = 0 ORDER BY group_order ASC");
}

function get_available_channels ($playlist) {
    global $sql;
    $result = [];
    // Live streams
    $streams = $sql->sql_select_array_query("SELECT  `id`, `group_id`, `source_order`, `source_tv_archive`, `source_tv_archive_duration`, `stream_tvg_name`, `stream_tvg_id`, `stream_tvg_logo`, `stream_order`, UNIX_TIMESTAMP(created_at) as 'added', (SELECT group_name FROM `groups` WHERE id = group_id) as 'category_name' FROM `live` WHERE `user_id` = '{$playlist['user_id']}' AND `playlist_id` = '{$playlist['id']}' AND `stream_is_hidden` = 0 AND `sync_is_removed` = 0 ORDER BY group_id, stream_order ASC");
    foreach ($streams as $stream) {
        $result["{$stream['id']}"] = [
            'num'                 => $stream['stream_order'],
            'name'                => $stream['stream_tvg_name'],
            'stream_type'         => 'live',
            'type_name'           => 'Live Streams',
            'stream_id'           => $stream['id'],
            'stream_icon'         => $stream['stream_tvg_logo'],
            'epg_channel_id'      => $stream['stream_tvg_id'],
            'added'               => $stream['added'],
            'category_name'       => $stream['category_name'],
            'category_id'         => $stream['group_id'],
            'series_no'           => null,
            'live'                => 1,
            'container_extension' => null,
            'custom_sid'          => '',
            'tv_archive'          => $stream['source_tv_archive'],
            'direct_source'       => '',
            'tv_archive_duration' => $stream['source_tv_archive_duration']
        ];
    }
    return $result;
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
if (!empty($subscription) && !in_array($subscription['playlist_type'], [0, 1])) {
    http_response_code(401);
    header('Status: 401 Unauthorized');
    exit;
}
 
/************************************************************************************/
/*																					*/
/*				Route API Request		 											*/
/*																					*/
/************************************************************************************/
if (!empty($playlist) && boolval($playlist['enabled']) && !empty($subscription) && active_subscription($subscription)) {
    switch (array_search($parameters['action'], $actions)) {

        // GET EPG
        case 200:
            $response = [];
            if (!empty($parameters['stream_id'])) {
                $timeshift = intval($playlist['epg_offset']) * 3600;
                $channel = $sql->sql_select_array_query("SELECT l.stream_tvg_id, (SELECT id FROM `xmltv_stations` WHERE tvg_id = l.stream_tvg_id) as 'epg_id', (SELECT lang FROM `xmltv_stations` WHERE tvg_id = l.stream_tvg_id) as 'lang' FROM `live` l WHERE id = '{$parameters['stream_id']}' AND user_id = '{$playlist['user_id']}' AND playlist_id = '{$playlist['id']}' AND stream_tvg_id IS NOT NULL");
                if (count($channel) > 0) {
                    $channel = $channel[0];
                    if (!empty($parameters['from_now']) && intval($parameters['from_now']) > 0) {
                        $epg = $sql->sql_select_array_query("SELECT UNIX_TIMESTAMP(DATE_ADD(p.start, INTERVAL p.offset + {$timeshift} second)) as 'start', UNIX_TIMESTAMP(DATE_ADD(p.stop, INTERVAL p.offset + {$timeshift} second)) as 'stop', p.title, p.description FROM `xmltv_programmes` p WHERE tvg_id = '{$channel['stream_tvg_id']}' AND `start` >= DATE_ADD(NOW(), INTERVAL p.offset + {$timeshift} second) ORDER BY `start`");
                    } else {
                        $epg = $sql->sql_select_array_query("SELECT UNIX_TIMESTAMP(DATE_ADD(p.start, INTERVAL p.offset + {$timeshift} second)) as 'start', UNIX_TIMESTAMP(DATE_ADD(p.stop, INTERVAL p.offset + {$timeshift} second)) as 'stop', p.title, p.description FROM `xmltv_programmes` p WHERE tvg_id = '{$channel['stream_tvg_id']}' ORDER BY `start`");
                    }
                    if (count($epg) > 0) {
                        foreach ($epg as $index => $programme) {
                            $response[] = [
                                'channel_id'  => $channel['stream_tvg_id'],
                                'description' => base64_encode($programme['description']),
                                'end'         => $programme['stop'],
                                'epg_id'      => $channel['epg_id'],
                                'id'          => $index,
                                'lang'        => $channel['lang'],
                                'start'       => $programme['start'],
                                'title'       => base64_encode($programme['title'])
                            ];
                        }
                    }
                }
            }
            break;

        // Authentication/Groups/Streams
        default:
            $response = [
                'user_info' => [
                    'active_cons'            => get_active_connections($playlist),
                    'allowed_output_formats' => $output_formats[$playlist['api_output_format']],
                    'auth'                   => 1,
                    'created_at'             => $playlist['created_at'],
                    'exp_date'               => $subscription['expire'],
                    'is_trial'               => 0,
                    'max_connections'        => $playlist['source_max_connections'],
                    'password'               => $playlist['api_password'],
                    'username'               => $playlist['api_username'],
                    'status'                 => get_status($playlist, $subscription)
                ],
                'server_info' => [
                    'url'             => $_SERVER['HTTP_HOST'], 
                    'port'            => $ports['http'], 
                    'https_port'      => $ports['https'],
                    'server_protocol' => 'http'
                ],
                'categories' => [
                    'live'   => get_categories($playlist, 1),
                    'radio'  => [],
                    'series' => get_categories($playlist, 3),
                    'movie'  => get_categories($playlist, 2)
                ],
                'available_channels' => get_available_channels($playlist)
            ];
            break;


    }
    echo_response($response);
} else {
    echo_response(['user_info' => ['auth' => 0]]);
}