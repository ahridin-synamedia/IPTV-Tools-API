<?php

/************************************************************************************/
/*																					*/
/*				player_api.php [ IPTV-Tools Xtream API ]							*/
/*																					*/
/*				Author	: Ernst Reidinga											*/
/*				Date 	: 27/06/2021												*/
/*				Version	: 1.0 (Compat XC V2.0)										*/
/*																					*/
/*              Stream type / Category type:                                        */
/*               1 - Live                                                           */
/*               2 - Movie                                                          */
/*               3 - Series                                                         */
/*               4 - Radio                                                          */
/*                                                                                  */
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
    200 => 'get_vod_categories',
    201 => 'get_live_categories',
    202 => 'get_live_streams',
    203 => 'get_vod_streams',
    204 => 'get_series_info',
    205 => 'get_short_epg',
    206 => 'get_series_categories',
    207 => 'get_simple_data_table',
    208 => 'get_series',
    209 => 'get_vod_info'
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

function tmdb_value($value, $alternate = "", $prefix = "") {
    return isset($value) && !empty($value) ? $prefix . $value : $alternate;
}

function parse_parameters() {
    $parameters = [
        "username",
        "password",
        "action",
        "category_id",
        "series_id",
        "stream_id",
        "vod_id",
        "items_per_page",
        "offset",
        "limit"
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

function get_categories ($playlist, $group_type, $full = false) {
    global $sql;
    global $offset;
    global $limit;
    $fields = $full === true ? "*, id AS category_id, group_name AS category_name, '1' AS parent_id" : "id AS category_id, group_name AS category_name, '1' AS parent_id";
    return $sql->sql_select_array_query("SELECT {$fields} FROM `groups` WHERE `user_id` = '{$playlist['user_id']}' AND `playlist_id` = '{$playlist['id']}' AND `group_type` = '{$group_type}' AND `group_is_hidden` = 0 ORDER BY group_order ASC LIMIT {$offset}, {$limit}");
}

function get_live_streams ($playlist, $group) {
    global $sql;
    global $offset;
    global $limit;
    return $sql->sql_select_array_query("SELECT *, UNIX_TIMESTAMP(created_at) as added FROM `live` WHERE `user_id` = '{$playlist['user_id']}' AND `playlist_id` = '{$playlist['id']}' AND `group_id` = '{$group['id']}' AND `stream_is_hidden` = 0 AND `sync_is_removed` = 0 ORDER BY stream_order ASC LIMIT {$offset}, {$limit}");
}

function get_movie_streams ($playlist, $group) {
    global $sql;
    global $offset;
    global $limit;
    return $sql->sql_select_array_query("SELECT *, UNIX_TIMESTAMP(created_at) AS added FROM `movie` WHERE `user_id` = '{$playlist['user_id']}' AND `playlist_id` = '{$playlist['id']}' AND `group_id` = '{$group['id']}' AND tmdb IS NOT NULL AND tmdb <> '' AND `stream_is_hidden` = 0 AND `sync_is_removed` = 0 ORDER BY stream_order ASC LIMIT {$offset}, {$limit}");
}

function get_movie ($playlist, $movie_id) {
    global $sql;
    $result = $sql->sql_select_array_query("SELECT *, UNIX_TIMESTAMP(created_at) AS added FROM `movie` WHERE `user_id` = '{$playlist['user_id']}' AND `playlist_id` = '{$playlist['id']}' AND `id` = '{$movie_id}'");
    return !empty($result) ? $result[0] : null;
}

function get_series ($playlist, $group) {
    global $sql;
    global $offset;
    global $limit;
    return $sql->sql_select_array_query("SELECT DISTINCT e.serie_name, e.source_serie_id as `serie_id`, e.stream_order as `order`, s.tmdb, s.credits, s.videos FROM `episodes` e, `series_tmdb` s WHERE s.user_id = e.user_id AND s.playlist_id = e.playlist_id AND s.tmdb_id = e.tmdb_id AND e.user_id = '{$playlist['user_id']}' AND e.playlist_id = '{$playlist['id']}' and group_id = '{$group['id']}' LIMIT {$offset}, {$limit}");
}

function get_serie ($playlist, $serie_id) {
    global $sql;
    $result = $sql->sql_select_array_query("SELECT DISTINCT e.serie_name, e.group_id, e.source_serie_id as `serie_id`, e.stream_order as `order`, s.tmdb, s.credits, s.videos FROM `episodes` e, `series_tmdb` s WHERE s.user_id = e.user_id AND s.playlist_id = e.playlist_id AND s.tmdb_id = e.tmdb_id AND e.user_id = '{$playlist['user_id']}' AND e.playlist_id = '{$playlist['id']}' and e.source_serie_id = '{$serie_id}'");
    return count($result) >= 1 ? $result[0] : [];
}

function get_serie_episodes ($playlist, $serie_id) {
    global $sql;
    return $sql->sql_select_array_query("SELECT *, UNIX_TIMESTAMP(created_at) as added FROM `episodes` WHERE `user_id` = '{$playlist['user_id']}' AND playlist_id = '{$playlist['id']}' and source_serie_id = '{$serie_id}' AND `stream_is_hidden` = 0 AND `sync_is_removed` = 0 ORDER BY stream_order ASC");
}

function get_cast ($credits) {
    $result = "";
    if (!empty($credits) && isset($credits['cast']) && !empty($credits['cast'])) {
        foreach ($credits['cast'] as $cast) {
            $result .= $cast['name'] . ', ';
        }   
    }
    return trim(rtrim($result, ', '));
}

function get_actors ($credits) {
    $result = "";
    if (!empty($credits) && isset($credits['cast']) && !empty($credits['cast'])) {
        foreach ($credits['cast'] as $cast) {
            if (stripos($cast['known_for_department'], 'acting') !== false) {
                $result .= $cast['name'] . ', ';
            }
        }   
    }
    return trim(rtrim($result, ', '));
}

function get_director ($credits) {
    $result = "";
    if (!empty($credits) && isset($credits['crew']) && !empty($credits['crew'])) {
        foreach ($credits['crew'] as $crew) {
            if (stripos($crew['job'], 'director') !== false) {
                $result .= $crew['name'] . ', ';
            }
        }   
    }
    return trim(rtrim($result, ', '));
}

function get_episode_runtime ($arr) {
    return  !empty($arr) ? $arr[0] : "0";
}

function get_genre ($genres) {
    $result = "";
    if (!empty($genres)) {
        foreach ($genres as $genre) {
            $result .= $genre['name'] . ', ';
        }   
    }
    return trim(rtrim($result, ', '));
}

function get_youtube_trailer ($videos) {
    if (!empty($videos)) {
        foreach ($videos as $video) {
            if (stripos($video['type'], 'trailer') !== false) {
                return $video['key'];
            }
        }   
    }
    return "";
}

function get_age ($tmdb) {
    return isset($tmdb['adult']) && boolval($tmdb['adult']) ? '18+' : '';
}

function get_country ($countries) {
    $result = "";
    if (!empty($countries)) {
        foreach ($countries as $country) {
            $result .= $country['name'] . ', ';
        }   
    }
    return trim(rtrim($result, ', '));
}

function get_seasons ($tmdb) {
    global $tmdb_prefix;
    $result = [];
    if (isset($tmdb['seasons']) && !empty($tmdb['seasons'])) {
        foreach ($tmdb['seasons'] as $season) {
            $result[] = [
                'air_date'      => tmdb_value($season['air_date'], ""),
                'cover'         => tmdb_value($season['poster_path'], '', $tmdb_prefix),
                'cover_big'     => tmdb_value($season['poster_path'], '', $tmdb_prefix),
                'episode_count' => tmdb_value($season['episode_count'], 0),
                'id'            => tmdb_value($season['id'], ""),
                'name'          => tmdb_value($season['name'], ""),
                'overview'      => tmdb_value($season['overview'], ""),
                'season_number' => tmdb_value($season['season_number'], 0)
            ];
        }
    }
    return $result;
}

function get_episodes ($episodes) {
    global $tmdb_prefix;
    $result = [];
    if (!empty($episodes)) {
        foreach ($episodes as $episode) {
            $result[$episode['serie_season']][] = [
                'added'               => $episode['added'],
                'container_extension' => $episode['source_container_extension'],
                'custom_sid'          => '',
                'direct_source'       => '',
                'episode_num'         => $episode['serie_episode'],
                'id'                  => $episode['id'],
                'season'              => $episode['serie_season'], 
                'title'               => tmdb_value($episode['tmdb']['name'], $episode['stream_tvg_name']),
                'info'                => [
                    'audio'         => [],
                    'bitrate'       => 0,
                    'duration'      => "",
                    'duration_secs' => 0,
                    'movie_image'   => tmdb_value($episode['tmdb']['still_path'], "", $tmdb_prefix),
                    'plot'          => tmdb_value($episode['tmdb']['overview'], ""),
                    'rating'        => tmdb_value($episode['tmdb']['vote_average'], ""),
                    'releasedate'   => tmdb_value($episode['tmdb']['air_date'], ""),
                    'season'        => tmdb_value($episode['tmdb']['season_number'], ""),
                    'tmdb_id'       => tmdb_value($episode['tmdb']['id'], ""),
                    'video'         => []
                ],
                
            ];
        }
    }
    return $result;
}

function format_epg ($channel, $epg, $short) {
    $result = [];
    foreach ($epg as $programme) {
        if ($short === true) {
            $result[] = [
                'epg_id'          => $channel['epg_id'],
                'title'           => base64_encode($programme['title']),
                'lang'            => $channel['lang'],
                'start'           => $programme['start'],
                'end'             => $programme['stop'],
                'description'     => base64_encode($programme['description']),
                'channel_id'      => $channel['stream_tvg_id'],
                'start_timestamp' => $programme['start_timestamp'],
                'stop_timestamp'  => $programme['stop_timestamp']
            ];
        } else {
            $result[] = [
                'epg_id'          => $channel['epg_id'],
                'title'           => base64_encode($programme['title']),
                'lang'            => $channel['lang'],
                'start'           => $programme['start'],
                'end'             => $programme['stop'],
                'description'     => base64_encode($programme['description']),
                'channel_id'      => $channel['stream_tvg_id'],
                'start_timestamp' => $programme['start_timestamp'],
                'stop_timestamp'  => $programme['stop_timestamp'],
                'has_archive'     => $channel['source_tv_archive'] == 1 && !empty($channel['source_tv_archive_duration']) && time() > $programme['stop_timestamp'] && strtotime("-{$channel['source_tv_archive_duration']} days") <= $programme['stop_timestamp'] ? 1 : 0,
                'now_playing'     => $programme['start_timestamp'] <= time() && $programme['stop_timestamp'] >= time() ? 1 : 0
            ];
        }
    }
    return $result;
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
if (!empty($playlist) && boolval($playlist['enabled']) && !empty($subscription)) {
    $response  = [];
    $offset    = !empty($parameters['offset']) ? $parameters['offset'] : 0;
    $limit     = !empty($parameters['limit'])  ? $parameters['limit']  : 1000000;
    $timeshift = intval($playlist['epg_offset']) * 3600;

    switch (array_search($parameters['action'], $actions)) {

        // VOD Categories
        case 200: 
            $response = get_categories($playlist, 2);
            break;

        // Live Categories
        case 201: 
            $response = get_categories($playlist, 1);
            break;

        // Live Streams
        case 202:
            $categories = get_categories($playlist, 1, true);
            foreach ($categories as $category) {
                if (!empty($parameters['category_id']) && $parameters['category_id'] != $category['id']) {
                    continue;
                }
                $streams = get_live_streams($playlist, $category);
                foreach ($streams as $stream) {
                    $response[] = [
                        'added'               => $stream['added'],
                        'category_id'         => $category['id'],
                        'custom_sid'          => '',
                        'direct_source'       => '',
                        'epg_channel_id'      => $stream['stream_tvg_id'],
                        'name'                => $stream['stream_tvg_name'],
                        'num'                 => $stream['stream_order'],
                        'stream_icon'         => $stream['stream_tvg_logo'],
                        'stream_id'           => $stream['id'],
                        'stream_type'         => $stream_type[1],
                        'tv_archive'          => $stream['source_tv_archive'],
                        'tv_archive_duration' => $stream['source_tv_archive_duration']
                    ];
                }
            }
            break;

        // VOD Streams
        case 203:
            $categories = get_categories($playlist, 2, true);
            foreach ($categories as $category) {
                if (!empty($parameters['category_id']) && $parameters['category_id'] != $category['id']) {
                    continue;
                }
                $streams = get_movie_streams($playlist, $category);
                foreach ($streams as $stream) {
                    $response[] = [
                        'added'               => $stream['added'],
                        'category_id'         => $category['id'],
                        'container_extension' => $stream['source_container_extension'],
                        'custom_sid'          => '',
                        'direct_source'       => '',
                        'name'                => tmdb_value($stream['tmdb']['title'], $stream['movie_name']),
                        'num'                 => $stream['stream_order'],
                        'rating'              => tmdb_value($stream['tmdb']['vote_average'], "0"),
                        'rating_5based'       => number_format(tmdb_value($stream['tmdb']['vote_average'], "0") * 0.5, 1) + 0,
                        'stream_icon'         => tmdb_value($stream['tmdb']['poster_path'], $stream['stream_tvg_logo'], $tmdb_prefix),
                        'stream_id'           => $stream['id'],
                        'stream_type'         => $stream_type[2],
                    ];
                }
            }
            break;

        // Series Info
        case 204:
            if (!empty($parameters['series_id'])) {
                $serie    = get_serie($playlist, $parameters['series_id']);
                $episodes = get_serie_episodes($playlist, $parameters['series_id']);
                $response = [
                    'seasons'  => get_seasons($serie['tmdb']),
                    'episodes' => get_episodes($episodes),
                    'info'     => [
                        'backdrop_path'    => [tmdb_value($serie['tmdb']['backdrop_path'], "", $tmdb_prefix)],
                        'cast'             => get_cast($serie['credits']),
                        'category_id'      => $serie['group_id'],
                        'cover'            => tmdb_value($serie['tmdb']['poster_path'], "", $tmdb_prefix),
                        'director'         => get_director($serie['credits']),
                        'episode_run_time' => get_episode_runtime($serie['tmdb']['episode_run_time']),
                        'genre'            => get_genre($serie['tmdb']['genres']),
                        'last_modified'    => "0",
                        'name'             => tmdb_value($serie['tmdb']['name'], $serie['serie_name']),
                        'plot'             => tmdb_value($serie['tmdb']['overview'], ""),
                        'rating'           => tmdb_value($serie['tmdb']['vote_average'], "0"),
                        'rating_5based'    => number_format(tmdb_value($serie['tmdb']['vote_average'], "0") * 0.5, 1) + 0,
                        'releaseDate'      => tmdb_value($serie['tmdb']['first_air_date'], ""),
                        'youtube_trailer'  => get_youtube_trailer($serie['videos'])
                    ]
                ];
            }
            break;
            

        // Short EPG
        case 205:
            $response = ['epg_listings' => []];
            if (!empty($parameters['stream_id'])) {
                $channel = $sql->sql_select_array_query("SELECT l.stream_tvg_id, (SELECT id FROM `xmltv_stations` WHERE tvg_id = l.stream_tvg_id) as 'epg_id', (SELECT lang FROM `xmltv_stations` WHERE tvg_id = l.stream_tvg_id) as 'lang' FROM `live` l WHERE id = '{$parameters['stream_id']}' AND user_id = '{$playlist['user_id']}' AND playlist_id = '{$playlist['id']}' AND stream_tvg_id IS NOT NULL");
                $limit   = !empty($parameters['limit'])  ? $parameters['limit']  : 4;
                if (count($channel) > 0) {
                    $channel = $channel[0];
                    $d       = gmdate("Y-m-d H:i:s");
                    $epg     = $sql->sql_select_array_query("SELECT DATE_ADD(p.start, INTERVAL p.offset + {$timeshift} second) as 'start', UNIX_TIMESTAMP(DATE_ADD(p.start, INTERVAL p.offset + {$timeshift} second)) as 'start_timestamp', DATE_ADD(p.stop, INTERVAL p.offset + {$timeshift} second) as 'stop', UNIX_TIMESTAMP(DATE_ADD(p.stop, INTERVAL p.offset + {$timeshift} second)) as 'stop_timestamp', p.title, p.description FROM `xmltv_programmes` p WHERE tvg_id = '{$channel['stream_tvg_id']}' AND ('{$d}' BETWEEN `start` AND `stop` OR `start` >= '{$d}') ORDER BY `start` LIMIT {$limit}");
                    if (count($epg) > 0) {
                        $response['epg_listings'] = format_epg($channel, $epg, true);
                    }
                }
            }
            break;

        // Series Categories
        case 206: 
            $response = get_categories($playlist, 3);
            break;

        // Simple Data Table
        case 207:
            $response = ['epg_listings' => []];
            if (!empty($parameters['stream_id'])) {
                $channel = $sql->sql_select_array_query("SELECT l.stream_tvg_id, l.source_tv_archive, l.source_tv_archive_duration, (SELECT id FROM `xmltv_stations` WHERE tvg_id = l.stream_tvg_id) as 'epg_id', (SELECT lang FROM `xmltv_stations` WHERE tvg_id = l.stream_tvg_id) as 'lang' FROM `live` l WHERE id = '{$parameters['stream_id']}' AND user_id = '{$playlist['user_id']}' AND playlist_id = '{$playlist['id']}' AND stream_tvg_id IS NOT NULL");
                if (count($channel) > 0) {
                    $channel = $channel[0];
                    $epg     = $sql->sql_select_array_query("SELECT DATE_ADD(p.start, INTERVAL p.offset + {$timeshift} second) as 'start', UNIX_TIMESTAMP(DATE_ADD(p.start, INTERVAL p.offset + {$timeshift} second)) as 'start_timestamp', DATE_ADD(p.stop, INTERVAL p.offset + {$timeshift} second) as 'stop', UNIX_TIMESTAMP(DATE_ADD(p.stop, INTERVAL p.offset + {$timeshift} second)) as 'stop_timestamp', p.title, p.description FROM `xmltv_programmes` p WHERE tvg_id = '{$channel['stream_tvg_id']}' ORDER BY `start`");
                    if (count($epg) > 0) {
                        $response['epg_listings'] = format_epg($channel, $epg, false);
                    }
                }
            }
            break;

        // Series
        case 208:
            $categories = get_categories($playlist, 3, true);
            foreach ($categories as $category) {
                if (!empty($parameters['category_id']) && $parameters['category_id'] != $category['id']) {
                    continue;
                }
                $series = get_series($playlist, $category);
                foreach ($series as $serie) {
                    $response[] = [
                        'backdrop_path'       => [tmdb_value($serie['tmdb']['backdrop_path'], "", $tmdb_prefix)],
                        'cast'                => get_cast($serie['credits']),
                        'category_id'         => $category['id'],
                        'cover'               => tmdb_value($serie['tmdb']['poster_path'], "", $tmdb_prefix),
                        'director'            => get_director($serie['credits']),
                        'episode_run_time'    => get_episode_runtime($serie['tmdb']['episode_run_time']),
                        'genre'               => get_genre($serie['tmdb']['genres']),
                        'last_modified'       => "0",
                        'name'                => tmdb_value($serie['tmdb']['name'], $serie['serie_name']),
                        'num'                 => $serie['order'],
                        'plot'                => tmdb_value($serie['tmdb']['overview'], ""),
                        'rating'              => tmdb_value($serie['tmdb']['vote_average'], "0"),
                        'rating_5based'       => number_format(tmdb_value($serie['tmdb']['vote_average'], "0") * 0.5, 1) + 0,
                        'releaseDate'         => tmdb_value($serie['tmdb']['first_air_date'], ""),
                        'series_id'           => $serie['serie_id'],
                        'youtube_trailer'     => get_youtube_trailer($serie['videos'])
                    ];
                }
            }
            break;

        // VOD Info
        case 209:
            if (!empty($parameters['vod_id'])) {
                $movie = get_movie($playlist, $parameters['vod_id']);
                $response = [
                    'info' => [
                        'actors'                 => get_actors($movie['tmdb_credits']),
                        'age'                    => get_age($movie['tmdb']),
                        'audio'                  => [],
                        'backdrop_path'          => [tmdb_value($movie['tmdb']['backdrop_path'], "", $tmdb_prefix)],
                        'bitrate'                => 0,
                        'cast'                   => get_cast($movie['tmdb_credits']),
                        'country'                => get_country($movie['tmdb']['production_countries']),
                        'cover_big'              => tmdb_value($movie['tmdb']['poster_path'], $movie['stream_tvg_logo'], $tmdb_prefix),
                        'description'            => tmdb_value($movie['tmdb']['overview'], ""),
                        'director'               => get_director($movie['tmdb_credits']),
                        'duration'               => gmdate("H:i:s", tmdb_value($movie['tmdb']['runtime'] * 60, 0)),
                        'duration_secs'          => tmdb_value($movie['tmdb']['runtime'] * 60, 0),
                        'episode_run_time'       => tmdb_value($movie['tmdb']['runtime'], 0),
                        'genre'                  => get_genre($movie['tmdb']['genres']),
                        'kinopoisk_url'          => tmdb_value($movie['tmdb']['id'], "", "https://www.themoviedb.org/movie/"),
                        'movie_image'            => tmdb_value($movie['tmdb']['poster_path'], $movie['stream_tvg_logo'], $tmdb_prefix),
                        'mpaa_rating'            => '',
                        'name'                   => tmdb_value($movie['tmdb']['title'], $movie['movie_name']),
                        'o_name'                 => tmdb_value($movie['tmdb']['original_title'], ''),
                        'plot'                   => tmdb_value($movie['tmdb']['overview'], ''),
                        'rating'                 => tmdb_value($movie['tmdb']['vote_average'], 0),
                        'rating_count_kinopoisk' => tmdb_value($movie['tmdb']['vote_count'], 0),
                        'releasedate'            => tmdb_value($movie['tmdb']['release_date'], ""),
                        'tmdb_id'                => tmdb_value($movie['tmdb']['id'], $movie['tmdb_id']),
                        'video'                  => [],
                        'youtube_trailer'        => get_youtube_trailer($movie['tmdb_videos'])
                    ],
                    'movie_data' => [
                        'added'               => $movie['added'],
                        'category_id'         => $movie['group_id'],
                        'container_extension' => $movie['source_container_extension'],
                        'custom_sid'          => '',
                        'direct_source'       => '',
                        'name'                => tmdb_value($movie['tmdb']['title'], $movie['movie_name']),
                        'stream_id'           => $movie['id']
                    ]
                ];
            }
            break;

        // Authentication
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
                    'message'                => $playlist['api_message'],
                    'password'               => $playlist['api_password'],
                    'username'               => $playlist['api_username'],
                    'status'                 => get_status($playlist, $subscription)
                ],
                'server_info' => [
                    'url'             => $_SERVER['HTTP_HOST'], 
                    'port'            => $ports['http'], 
                    'https_port'      => $ports['https'],
                    'server_protocol' => 'http',
                    'rtmp_port'       => $ports['rtmp'],
                    'timezone'        => date_default_timezone_get(), 
                    'timestamp_now'   => time(), 
                    'time_now'        => date('Y-m-d H:i:s')
                ]
            ];
            break;


    }
    echo_response($response);
} else {
    echo_response(['user_info' => ['auth' => 0]]);
}