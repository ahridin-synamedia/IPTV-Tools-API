<?php

/**********************************************************************************/
/*																				  */
/*				sync_playlist.php (Cron Executed Script)						  */
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

/************************************************************************************/
/*																					*/
/*				Allow bigger memory usage									 		*/
/*																					*/
/************************************************************************************/
ini_set('memory_limit', '-1');
set_time_limit(0);

/************************************************************************************/
/*																					*/
/*				Set Timezone to Amsterdam									 		*/
/*																					*/
/************************************************************************************/
date_default_timezone_set('Europe/Amsterdam');

/************************************************************************************/
/*																					*/
/*				Playlist                                    					    */
/*																					*/
/************************************************************************************/
$arguments = arguments($argv);
if (!array_key_exists('user', $arguments) || !array_key_exists('playlist', $arguments)) {
    die('Error: Need --user and --playlist arguments!');
}

$user_id     = $arguments['user'];
$playlist_id = $arguments['playlist'];
$playlist    = $sql->sql_select_array_query("SELECT * FROM `playlist` WHERE `user_id` = '{$user_id}' AND `id` = '{$playlist_id}' LIMIT 1");

if (count($playlist) === 0) {
    die("Error: Playlist with id {$playlist_id} and user_id {$user_id} is not found!");
} else {
    $sql->sql_update('sync_playlist', [
        'active' => 1
    ], [
        'user_id'     => $user_id,
        'playlist_id' => $playlist_id
    ]);
}

$sync_live   = $playlist[0]['sync_live'];
$sync_movies = $playlist[0]['sync_movies'];
$sync_series = $playlist[0]['sync_series'];

$host     = $playlist[0]['source_host'];
$port     = $playlist[0]['source_port'];
$username = $playlist[0]['source_username'];
$password = $playlist[0]['source_password'];

$base_url = !empty($port) ? "http://{$host}:{$port}/player_api.php?username={$username}&password={$password}" : "http://{$host}/player_api.php?username={$username}&password={$password}";

/************************************************************************************/
/*																					*/
/*				Serie Episodes (Streams)                       					    */
/*																					*/
/************************************************************************************/
function series_episodes ($baseurl, $series_id) {
    $res      = curl_http_get("{$baseurl}&action=get_series_info&series_id={$series_id}");
    $episodes = [];
    if (isset($res['episodes'])) {
        foreach ($res['episodes'] as $season) {
            foreach ($season as $episode) {
                $episodes[] = $episode;
            }
        }
    }
    return $episodes;
}

/************************************************************************************/
/*																					*/
/*				Delete "old" groups and streams                 				    */
/*																					*/
/************************************************************************************/
$live_ids = implode(',', $sync_live);
$sql->sql_query("DELETE FROM `groups` WHERE `source_group_type` = 1 AND group_is_custom = 0 AND `source_category_id` NOT IN ({$live_ids})");
$sql->sql_query("DELETE FROM `live` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND stream_is_custom = 0 AND group_id NOT IN (select id FROM groups)");

$movie_ids = implode(',', $sync_movies);
$sql->sql_query("DELETE FROM `groups` WHERE `source_group_type` = 2 AND group_is_custom = 0 AND `source_category_id` NOT IN ({$movie_ids})");
$sql->sql_query("DELETE FROM `movie` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND stream_is_custom = 0 AND group_id NOT IN (select id FROM groups)");

$series_ids = implode(',', $sync_series);
$sql->sql_query("DELETE FROM `groups` WHERE `source_group_type` = 3 AND group_is_custom = 0 AND `source_category_id` NOT IN ({$series_ids})");
$sql->sql_query("DELETE FROM `episodes` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND stream_is_custom = 0 AND group_id NOT IN (select id FROM groups)");

/************************************************************************************/
/*																					*/
/*				Synchronize Categories (Groups)                 				    */
/*																					*/
/************************************************************************************/
$categories = curl_multi_http_get([
    "{$base_url}&action=get_live_categories",
    "{$base_url}&action=get_vod_categories",
    "{$base_url}&action=get_series_categories"
]);

// Live
foreach ($categories[0] as $index => $category) {
    if (in_array($category['category_id'], $sync_live)) {
        $sql->sql_playlist_insert_update('groups', [
            'user_id'            => $user_id,
            'playlist_id'        => $playlist_id,
            'source_category_id' => $category['category_id'],
            'source_group_name'  => $category['category_name'],
            'source_group_order' => $index +1,
            'source_group_type'  => 1,
            'group_is_custom'    => 0,
            'group_order'        => $index +1,
            'group_type'         => 1,
            'group_name'         => $category['category_name']
        ]);
    }
}

// Movies
foreach ($categories[1] as $index => $category) {
    if (in_array($category['category_id'], $sync_movies)) {
        $sql->sql_playlist_insert_update('groups', [
            'user_id'            => $user_id,
            'playlist_id'        => $playlist_id,
            'source_category_id' => $category['category_id'],
            'source_group_name'  => $category['category_name'],
            'source_group_order' => $index +1,
            'source_group_type'  => 2,
            'group_is_custom'    => 0,
            'group_order'        => $index +1,
            'group_type'         => 2,
            'group_name'         => $category['category_name']
        ]);
    }
}

// Series
foreach ($categories[2] as $index => $category) {
    if (in_array($category['category_id'], $sync_series)) {
        $sql->sql_playlist_insert_update('groups', [
            'user_id'            => $user_id,
            'playlist_id'        => $playlist_id,
            'source_category_id' => $category['category_id'],
            'source_group_name'  => $category['category_name'],
            'source_group_order' => $index +1,
            'source_group_type'  => 3,
            'group_is_custom'    => 0,
            'group_order'        => $index +1,
            'group_type'         => 3,
            'group_name'         => $category['category_name']
        ]);
    }
}

/************************************************************************************/
/*																					*/
/*				Synchronize Live Streams                         				    */
/*																					*/
/************************************************************************************/
$sql->sql_query("UPDATE `live` SET sync_is_new = 0 WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}'");
$sql->sql_query("DELETE FROM `live` WHERE sync_is_removed = 1 AND user_id = '{$user_id}' AND playlist_id = '{$playlist_id}'");
$live_streams = curl_http_get("{$base_url}&action=get_live_streams");
$category_id  = "";
$stream_ids   = array();
foreach ($live_streams as $stream) {
    if (in_array($stream['category_id'], $sync_live)) {
        if ($category_id !== $stream['category_id']) {
            $category_id = $stream['category_id'];
            $group_id = $sql->sql_select_array_query("SELECT id FROM `groups` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND source_group_type = 1 AND group_is_custom = 0 AND source_category_id = '{$category_id}' LIMIT 1")[0]['id'];
        }
        $sql->sql_playlist_insert_update('live', [
            'user_id'                    => $user_id,
            'playlist_id'                => $playlist_id,
            'group_id'                   => $group_id,
            'source_tvg_name'            => $stream['name'],
            'source_tvg_id'              => empty($stream['epg_channel_id']) ? "" : $stream['epg_channel_id'],
            'source_tvg_logo'            => empty($stream['stream_icon'])    ? "" : $stream['stream_icon'],
            'source_order'               => $stream['num'],
            'source_stream_id'           => $stream['stream_id'],
            'source_stream_type'         => 1,
            'source_tv_archive'          => $stream['tv_archive'],
            'source_tv_archive_duration' => $stream['tv_archive_duration'],
            'sync_is_new'                => 1,
            'stream_tvg_name'            => $stream['name'],
            'stream_tvg_id'              => empty($stream['epg_channel_id']) ? "" : $stream['epg_channel_id'],
            'stream_tvg_logo'            => empty($stream['stream_icon'])    ? "" : $stream['stream_icon'],
            'stream_order'               => $stream['num']
        ]);
        array_push($stream_ids, $stream['stream_id']);
    }
}
$stream_ids = implode(',', $stream_ids);
$sql->sql_query("UPDATE `live` SET sync_is_removed = true WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND stream_is_custom = 0 AND source_stream_id NOT IN ({$stream_ids})");

/************************************************************************************/
/*																					*/
/*				Synchronize Movies                               				    */
/*																					*/
/************************************************************************************/
$sql->sql_query("UPDATE `movie` SET sync_is_new = false WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}'");
$sql->sql_query("DELETE FROM `movie` WHERE sync_is_removed = true AND user_id = '{$user_id}' AND playlist_id = '{$playlist_id}'");
$movie_streams = curl_http_get("{$base_url}&action=get_vod_streams");
$category_id   = "";
$stream_ids    = array();
foreach ($movie_streams as $stream) {
    if (in_array($stream['category_id'], $sync_movies)) {
        if ($category_id !== $stream['category_id']) {
            $category_id = $stream['category_id'];
            $group_id = $sql->sql_select_array_query("SELECT id FROM `groups` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND source_group_type = 2 AND group_is_custom = 0 AND source_category_id = '{$category_id}' LIMIT 1")[0]['id'];
        }
        $sql->sql_playlist_insert_update('movie', [
            'user_id'                    => $user_id,
            'playlist_id'                => $playlist_id,
            'group_id'                   => $group_id,
            'source_tvg_name'            => $stream['name'],
            'source_tvg_logo'            => $stream['stream_icon'],
            'source_order'               => $stream['num'],
            'source_stream_id'           => $stream['stream_id'],
            'source_stream_type'         => 2,
            'source_container_extension' => $stream['container_extension'],
            'sync_is_new'                => 1,
            'stream_tvg_name'            => $stream['name'],
            'stream_tvg_logo'            => $stream['stream_icon'],
            'stream_order'               => $stream['num']
        ]);
        array_push($stream_ids, $stream['stream_id']);
    }
}
$stream_ids = implode(',', $stream_ids);
$sql->sql_query("UPDATE `movie` SET sync_is_removed = true WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND stream_is_custom = 0 AND source_stream_id NOT IN ({$stream_ids})");

/************************************************************************************/
/*																					*/
/*				Synchronize Series                               				    */
/*																					*/
/************************************************************************************/
$sql->sql_query("UPDATE `episodes` SET sync_is_new = false WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}'");
$sql->sql_query("DELETE FROM `episodes` WHERE sync_is_removed = true AND user_id = '{$user_id}' AND playlist_id = '{$playlist_id}'");
$series = curl_http_get("{$base_url}&action=get_series");
$category_id   = "";
$stream_ids    = array();
foreach ($series as $serie) {
    if (in_array($serie['category_id'], $sync_series)) {
        if ($category_id !== $serie['category_id']) {
            $category_id = $serie['category_id'];
            $group_id = $sql->sql_select_array_query("SELECT id FROM `groups` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND source_group_type = 3 AND group_is_custom = 0 AND source_category_id = '{$category_id}' LIMIT 1")[0]['id'];
        }
        foreach (series_episodes($base_url, $serie['series_id']) as $episode) {
            $sql->sql_playlist_insert_update('episodes', [
                'user_id'                    => $user_id,
                'playlist_id'                => $playlist_id,
                'group_id'                   => $group_id,
                'source_tvg_name'            => $episode['title'],
                'source_tvg_logo'            => $serie['cover'],
                'source_order'               => $serie['num'],
                'source_stream_id'           => $episode['id'],
                'source_stream_type'         => 3,
                'source_container_extension' => $episode['container_extension'],
                'source_serie_id'            => $serie['series_id'],
                'sync_is_new'                => 1,
                'stream_tvg_name'            => $episode['title'],
                'stream_tvg_logo'            => $serie['cover'],
                'stream_order'               => $serie['num'],
                'serie_name'                 => $serie['name'],
                'serie_season'               => $episode['season'],
                'serie_episode'              => $episode['episode_num'],
                'serie_trailer'              => $serie['youtube_trailer'],
                'tmdb_episode_id'            => isset($episode['info']['tmdb_id']) ? $episode['info']['tmdb_id'] : ""
            ]);
            array_push($stream_ids, $episode['id']);
        }
    }
}
$stream_ids = implode(',', $stream_ids);
$sql->sql_query("UPDATE `episodes` SET sync_is_removed = true WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND stream_is_custom = 0 AND source_stream_id NOT IN ({$stream_ids})");

/************************************************************************************/
/*																					*/
/*				Update Synchronization Date                         			    */
/*																					*/
/************************************************************************************/
$sql->sql_update('playlist', [
    'synced_at' => date('Y-m-d H:i:s')
], [
    'user_id' => $user_id,
    'id'      => $playlist_id
]);

/************************************************************************************/
/*																					*/
/*				Remove from Synchronization table                  				    */
/*																					*/
/************************************************************************************/
$sql->sql_delete('sync_playlist', [
    'user_id'     => $user_id,
    'playlist_id' => $playlist_id
]);

/************************************************************************************/
/*																					*/
/*				Add to TMDB Synchronization table and run script   				    */
/*																					*/
/************************************************************************************/
$sql->sql_insert('sync_tmdb', [
    'user_id'     => $user_id,
    'playlist_id' => $playlist_id
]);
exec("/usr/local/bin/php /home/iptvtools/public_html/cron/sync_tmdb.php --user={$user_id} --playlist={$playlist_id} --newonly=true > /dev/null &");