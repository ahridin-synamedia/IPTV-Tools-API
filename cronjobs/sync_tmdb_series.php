<?php

/**********************************************************************************/
/*																				  */
/*				sync_tmdb_series.php (Manual Executed Script)					  */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 01/08/2021    								  		  */
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
$tmdb_api_key  = TMDB_API_KEY;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
/*				TMDB Functions                                 					    */
/*																					*/
/************************************************************************************/

// Find series by title and year
function get_series_tmdb_id ($title, $year) {
    global $tmdb_api_key;
    $_title = urlencode($title);
    $url = "https://api.themoviedb.org/3/search/tv?api_key={$tmdb_api_key}&query={$_title}&first_air_date_year={$year}";
    $results = curl_http_get($url);
    if (isset($results['results'])) {
        foreach ($results['results'] as $series) {
            if ((isset($series['name']) && strcasecmp($title, $series['name']) === 0) || (isset($series['original_name']) && strcasecmp($title, $series['original_name']) === 0)) {
                return $series['id'];
            }
        }
        return (count($results['results']) > 0 && isset($results['results'][0]) && isset($results['results'][0]['id'])) ? $results['results'][0]['id'] : '';
    }
    return '';
}

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
}

$tmdb_language   = $playlist[0]['tmdb_language'];
$update_new_only = isset($arguments['newonly']);

if ($update_new_only) {
    $series = $sql->sql_select_array_query("SELECT * FROM `episodes` WHERE `user_id` = '{$user_id}' AND `playlist_id` = '{$playlist_id}' AND `tmdb` IS NULL OR `tmdb` = ''");
} else {
    $series = $sql->sql_select_array_query("SELECT * FROM `episodes` WHERE `user_id` = '{$user_id}' AND `playlist_id` = '{$playlist_id}'");
}

$old_serie = "";
$old_tmdb_id = "";
foreach ($series as $serie) {
    if ($old_serie !== $serie['serie_name']) {
        if (empty($serie['tmdb_id'])) {
            $tmdb_id = get_series_tmdb_id($serie['serie_name'], $serie['serie_year']);
        } else {
            $tmdb_id = $serie['tmdb_id'];
        }
        $old_serie = $serie['serie_name'];
    }
    if (!empty($tmdb_id) && $old_tmdb_id !== $tmdb_id) {
        $old_tmdb_id = $tmdb_id;
        $serie_tmdb = curl_multi_http_get([
            "https://api.themoviedb.org/3/tv/{$tmdb_id}?api_key={$tmdb_api_key}&language={$tmdb_language}",
            "https://api.themoviedb.org/3/tv/{$tmdb_id}/credits?api_key={$tmdb_api_key}&language={$tmdb_language}",
            "https://api.themoviedb.org/3/tv/{$tmdb_id}/keywords?api_key={$tmdb_api_key}",
            "https://api.themoviedb.org/3/tv/{$tmdb_id}/similar?api_key={$tmdb_api_key}&language={$tmdb_language}",
            "https://api.themoviedb.org/3/tv/{$tmdb_id}/videos?api_key={$tmdb_api_key}&language={$tmdb_language},en"
        ]);
        $sql->sql_insert_update('series_tmdb', [
            'user_id'     => $user_id,
            'playlist_id' => $playlist_id,
            'tmdb_id'     => $tmdb_id,
            'tmdb'        => $serie_tmdb[0],
            'credits'     => $serie_tmdb[1],
            'keywords'    => isset($serie_tmdb[2]['results']) ? $serie_tmdb[2]['results'] : [],
            'similar'     => isset($serie_tmdb[3]['results']) ? $serie_tmdb[3]['results'] : [],
            'videos'      => isset($serie_tmdb[4]['results']) ? $serie_tmdb[4]['results'] : []
        ]);
    }
    $season  = $serie['serie_season'];
    $episode = $serie['serie_episode'];
    if (!empty($tmdb_id)) {
        $sql->sql_update('episodes', [
            'tmdb_id' => $tmdb_id,
            'tmdb'    => curl_http_get("https://api.themoviedb.org/3/tv/{$tmdb_id}/season/{$season}/episode/{$episode}?api_key={$tmdb_api_key}&language={$tmdb_language}")
        ], [
            'id' => $serie['id']
        ]);
    }
}