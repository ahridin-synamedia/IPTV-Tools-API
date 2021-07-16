<?php

/**********************************************************************************/
/*																				  */
/*				sync_tmdb.php (Cron Executed Script)						      */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 01/06/2021    								  		  */
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
/*				Extract Functions                                				    */
/*																					*/
/************************************************************************************/
function extract_between ($text, $left, $right) {
    $r = [];
    $e = 0;
    $p = 0;
    do {
        $p = strpos($text, $left, $e);
        $n = $p +1;
        $e = strpos($text, $right, $n);
        if ($p !== false && $e !== false) {
            $r[] = substr($text, $p +1, ($e - $p) -1);
        }
        $e++;
    } while ($p !== false && $e !== false);
    return $r;
}

function extract_tags ($text) {
    $r1 = extract_between($text, '[', ']');
    $r2 = extract_between($text, '(', ')');
    $r3 = extract_between($text, '|', '|');
    return array_merge($r1, $r2, $r3);
}

function string_is_year ($str) {
    $year = intval($str);
    return $year >= 1900 && $year <= intval(date("Y"));
}

function extract_year ($title) {
    if (preg_match("(19\d{2}|20(?:0\d|1[0-9]|2[0-9]))", $title, $n)) {
        return $n[0];
    }
    return '';
}

function extract_movie_info ($title) {
    $result = [
        'name' => '',
        'year' => ''
    ];
    if (preg_match("/^.+?(?=\\s*[(.]?(\\d{4}))/mi", $title, $n)) {
        $result['name'] = trim(rtrim($n[0], '-'));
        $result['year'] = $n[1];
    }
    if (count(extract_tags($result['name'])) === 0) {
        return $result;
    }
    $tags = extract_tags($title);
    $name = $title;
    $year = "";
    foreach ($tags as $tag) {
        if (string_is_year($tag)) {
            $year = $tag;
        }
        $name = str_replace('[' . $tag . ']', '', $name);
        $name = str_replace('(' . $tag . ')', '', $name);
        $name = str_replace('|' . $tag . '|', '', $name);
        $name = trim(rtrim(trim($name), '-'));
    }
    if (empty($year)) {
        $year = extract_year($title);
    }
    return [
        'name'	=> $name,
        'year'	=> $year
    ];
}

/************************************************************************************/
/*																					*/
/*				TMDB Functions                                 					    */
/*																					*/
/************************************************************************************/

// Find movie by title and year
function get_movie_tmdb_id ($title, $year) {
    global $tmdb_api_key;
    $_title = urlencode($title);
    $url = "https://api.themoviedb.org/3/search/movie?api_key={$tmdb_api_key}&query={$_title}&year={$year}";
    $results = curl_http_get($url)['results'];
    foreach ($results as $movies) {
        if ((isset($movies['name']) && strcasecmp($title, $movies['name']) === 0) || (isset($movies['original_name']) && strcasecmp($title, $movies['original_name']) === 0)) {
            return $series['id'];
        }
    }
    return count($results) > 0 ? $results[0]['id'] : '';
}

// Find series by title and year
function get_series_tmdb_id ($title) {
    global $tmdb_api_key;
    $_title = urlencode($title);
    $url = "https://api.themoviedb.org/3/search/tv?api_key={$tmdb_api_key}&query={$_title}";
    $results = curl_http_get($url)['results'];
    foreach ($results as $series) {
        if (strcasecmp($title, $series['name']) === 0 || strcasecmp($title, $series['original_name']) === 0) {
            return $series['id'];
        }
    }
    return count($results) > 0 ? $results[0]['id'] : '';
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
} else {
    $sql->sql_update('sync_tmdb', [
        'active' => true
    ], [
        'user_id'     => $user_id,
        'playlist_id' => $playlist_id
    ]);
}

$tmdb_language   = $playlist[0]['tmdb_language'];
$update_new_only = isset($arguments['newonly']);

/************************************************************************************/
/*																					*/
/*				Synchronize Movie Information                  					    */
/*																					*/
/************************************************************************************/
if ($update_new_only) {
    $movies = $sql->sql_select_array_query("SELECT * FROM `movie` WHERE `user_id` = '{$user_id}' AND `playlist_id` = '{$playlist_id}' AND `tmdb` IS NULL OR `tmdb` = ''");
} else {
    $movies = $sql->sql_select_array_query("SELECT * FROM `movie` WHERE `user_id` = '{$user_id}' AND `playlist_id` = '{$playlist_id}'");
}

foreach ($movies as $movie) {
    $movie_info  = extract_movie_info($movie['source_tvg_name']);
    $movie_title = $movie_info['name'];
    $movie_year  = $movie_info['year'];
    if (empty($movie['tmdb_id'])) {
        $tmdb_id = get_movie_tmdb_id($movie_title, $movie_year);
    } else {
        $tmdb_id = $movie['tmdb_id'];
    }
    if (!empty($tmdb_id)) {
        $movie_tmdb = curl_multi_http_get([
            "https://api.themoviedb.org/3/movie/{$tmdb_id}?api_key={$tmdb_api_key}&language={$tmdb_language}",
            "https://api.themoviedb.org/3/movie/{$tmdb_id}/credits?api_key={$tmdb_api_key}&language={$tmdb_language}",
            "https://api.themoviedb.org/3/movie/{$tmdb_id}/keywords?api_key={$tmdb_api_key}",
            "https://api.themoviedb.org/3/movie/{$tmdb_id}/similar?api_key={$tmdb_api_key}&language={$tmdb_language}",
            "https://api.themoviedb.org/3/movie/{$tmdb_id}/videos?api_key={$tmdb_api_key}&language={$tmdb_language},en"
        ]);
        $sql->sql_update('movie', [
            'movie_name'    => $movie_title,
            'movie_year'    => $movie_year,
            'tmdb_id'       => $tmdb_id,
            'tmdb'          => $movie_tmdb[0],
            'tmdb_credits'  => $movie_tmdb[1],
            'tmdb_keywords' => isset($movie_tmdb[2]['keywords']) ? $movie_tmdb[2]['keywords'] : [],
            'tmdb_similar'  => isset($movie_tmdb[3]['results'])  ? $movie_tmdb[3]['results']  : [],
            'tmdb_videos'   => isset($movie_tmdb[4]['results'])  ? $movie_tmdb[4]['results']  : []
        ], [
            'id' => $movie['id']
        ]);
    }
}

/************************************************************************************/
/*																					*/
/*				Synchronize Episodes Information                  					*/
/*																					*/
/************************************************************************************/
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
            $tmdb_id = get_series_tmdb_id($serie['serie_name']);
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

/************************************************************************************/
/*																					*/
/*				Remove from Synchronization table                  				    */
/*																					*/
/************************************************************************************/
$sql->sql_delete('sync_tmdb', [
    'user_id'     => $user_id,
    'playlist_id' => $playlist_id
]);