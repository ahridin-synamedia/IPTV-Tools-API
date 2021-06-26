<?php

/**********************************************************************************/
/*																				  */
/*				tmdb_top.php (Cron Executed Script)								  */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 16/05/2021    								  		  */
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
$tmdb_api_key = TMDB_API_KEY;
$ch           = curl_init();
$debug        = false;

/************************************************************************************/
/*																					*/
/*				CURL HTTP FUNCTIONS                             					*/
/*																					*/
/************************************************************************************/
// CURL HTTP JSON.
function curl_http_get_json ($url, $useragent = 'Mozilla/5.0 like Gecko') {
    global $ch;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_ENCODING,  '');
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    $output = curl_exec($ch);
    return json_decode($output, true);
}

/************************************************************************************/
/*																					*/
/*				SQL Functions                                   					*/
/*																					*/
/************************************************************************************/
function format_value ($tmdb_id, $title) {
    global $sql;
    return "('" . $sql->clean_string($tmdb_id) . "', '" . $sql->clean_string($title) . "'),";
}

/************************************************************************************/
/*																					*/
/*				TMDB FUNCTIONS                                   					*/
/*																					*/
/************************************************************************************/
// Get popular movies
function popular_movies ($pages = 50, $start = 1) {
    global $tmdb_api_key;
    global $sql;
    $movies  = [];
    for ($i = $start; $i <= $start + $pages; $i++) {
        $url = "https://api.themoviedb.org/3/movie/popular?api_key={$tmdb_api_key}&page={$i}";
        $movies = array_merge($movies, curl_http_get_json($url)['results']);
    }
    $query = "";
    foreach ($movies as $movie) {
        if (isset($movie['title']) && isset($movie['id'])) {
            $query .= format_value($movie['id'], $movie['title']);
        }
    }
    $sql->sql_query("DELETE FROM tmdb_movies_popular");
    $sql->sql_query(rtrim("INSERT INTO tmdb_movies_popular (tmdb_id, title) VALUES {$query}", ','));
}

// Get popular series
function popular_series ($pages = 50, $start = 1) {
    global $tmdb_api_key;
    global $sql;
    $series  = [];
    for ($i = $start; $i <= $start + $pages; $i++) {
        $url = "https://api.themoviedb.org/3/tv/popular?api_key={$tmdb_api_key}&page={$i}";
        $series = array_merge($series, curl_http_get_json($url)['results']);
    }
    $query = "";
    foreach ($series as $serie) {
        if (isset($serie['name']) && isset($serie['id'])) {
            $query .= format_value($serie['id'], $serie['name']);
        }
    }
    $sql->sql_query("DELETE FROM tmdb_series_popular");
    $sql->sql_query(rtrim("INSERT INTO tmdb_series_popular (tmdb_id, title) VALUES {$query}", ','));
}

// Get top rated movies
function top_rated_movies ($pages = 50, $start = 1) {
    global $tmdb_api_key;
    global $sql;
    $movies  = [];
    for ($i = $start; $i <= $start + $pages; $i++) {
        $url = "https://api.themoviedb.org/3/movie/top_rated?api_key={$tmdb_api_key}&page={$i}";
        $movies = array_merge($movies, curl_http_get_json($url)['results']);
    }
    $query = "";
    foreach ($movies as $movie) {
        if (isset($movie['title']) && isset($movie['id'])) {
            $query .= format_value($movie['id'], $movie['title']);
        }
    }
    $sql->sql_query("DELETE FROM tmdb_movies_top");
    $sql->sql_query(rtrim("INSERT INTO tmdb_movies_top (tmdb_id, title) VALUES {$query}", ','));
}

// Get top rated series
function top_rated_series ($pages = 50, $start = 1) {
    global $tmdb_api_key;
    global $sql;
    $series  = [];
    for ($i = $start; $i <= $start + $pages; $i++) {
        $url = "https://api.themoviedb.org/3/tv/top_rated?api_key={$tmdb_api_key}&page={$i}";
        $series = array_merge($series, curl_http_get_json($url)['results']);
    }
    $query = "";
    foreach ($series as $serie) {
        if (isset($serie['name']) && isset($serie['id'])) {
            $query .= format_value($serie['id'], $serie['name']);
        }
    }
    $sql->sql_query("DELETE FROM tmdb_series_top");
    $sql->sql_query(rtrim("INSERT INTO tmdb_series_top (tmdb_id, title) VALUES {$query}", ','));
}

// Get now playing in theaters movies
function now_playing_movies ($pages = 50, $start = 1) {
    global $tmdb_api_key;
    global $sql;
    $movies  = [];
    for ($i = $start; $i <= $start + $pages; $i++) {
        $url = "https://api.themoviedb.org/3/movie/now_playing?api_key={$tmdb_api_key}&page={$i}";
        $movies = array_merge($movies, curl_http_get_json($url)['results']);
    }
    $query = "";
    foreach ($movies as $movie) {
        if (isset($movie['title']) && isset($movie['id'])) {
            $query .= format_value($movie['id'], $movie['title']);
        }
    }
    $sql->sql_query("DELETE FROM tmdb_movies_cinema");
    $sql->sql_query(rtrim("INSERT INTO tmdb_movies_cinema (tmdb_id, title) VALUES {$query}", ','));
}

// Get on the air tv series
function on_the_air_series ($pages = 50, $start = 1) {
    global $tmdb_api_key;
    global $sql;
    $series  = [];
    for ($i = $start; $i <= $start + $pages; $i++) {
        $url = "https://api.themoviedb.org/3/tv/on_the_air?api_key={$tmdb_api_key}&page={$i}";
        $series = array_merge($series, curl_http_get_json($url)['results']);
    }
    $query = "";
    foreach ($series as $serie) {
        if (isset($serie['name']) && isset($serie['id'])) {
            $query .= format_value($serie['id'], $serie['name']);
        }
    }
    $sql->sql_query("DELETE FROM tmdb_series_tv");
    $sql->sql_query(rtrim("INSERT INTO tmdb_series_tv (tmdb_id, title) VALUES {$query}", ','));
}

/************************************************************************************/
/*																					*/
/*				MAIN FUNCTIONS                                   					*/
/*																					*/
/************************************************************************************/

// Popular
popular_movies();
popular_series();

// Top Rated
top_rated_movies();
top_rated_series();

// In Cinema / On TV
now_playing_movies();
on_the_air_series();

curl_close($ch);