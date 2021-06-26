<?php

/**********************************************************************************/
/*																				  */
/*				movie.php 				  			    					      */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 30/05/2021									  		  */
/*				Version	: 1.0													  */
/*																				  */
/**********************************************************************************/

class movie {
    
    // Movie class constructor
	function __construct () {
		
    }

    // Get movie streams for user
    function get ($user_id, $from, $amount, $simple = false) {
        global $sql;
        if ($simple === true) {
            return $sql->sql_select_array_query("SELECT `id`, `stream_tvg_name`, `source_tvg_name` FROM `movie` WHERE user_id = '{$user_id}'");
        }
        return $sql->sql_select_array_query("SELECT `id`, `user_id`, `playlist_id`, `group_id`, `stream_tvg_name`, `stream_tvg_id`, `stream_tvg_logo`, `stream_tvg_chno`, `stream_tvg_shift`, `stream_parent_code`, `stream_audio_track`, `stream_aspect_ratio`, `stream_order`, `stream_is_hidden`, `sync_is_new`, `sync_is_removed`, `movie_name`, `movie_year`, `tmdb_id`, (SELECT name FROM `playlist` WHERE `id` = s.playlist_id) AS `playlist`, (SELECT group_name FROM `groups` WHERE `id` = s.group_id) AS `group` FROM `movie` s WHERE user_id = '{$user_id}' LIMIT {$from}, {$amount}");
    }

    // Add new empty movie stream
    function add ($user_id) {
        global $sql;
        $source_stream_id = $this->new_custom_source_stream_id($user_id);
        if ($sql->sql_insert('movie', ['user_id' => $user_id, 'source_stream_id' => $source_stream_id, 'stream_is_custom' => true])) {
            return $sql->last_insert_id();
        } else {
            return $sql->sql_last_error();
        }
    }

    // Update movie stream
    function update ($user_id, $stream_id, $stream) {
        global $sql;
        if ($sql->sql_update('movie', $stream, ['user_id' => $user_id, 'id' => $stream_id])) {
            return true;
        } else {
            return $sql->sql_last_error();
        }
    }

    // Delete movie stream
    function delete ($user_id, $stream_id) {
        global $sql;
        return $sql->sql_delete('movie', ['user_id' => $user_id, 'id' => $stream_id]);
    }

    // Get count of streams
    function total ($user_id) {
        global $sql;
        $result = $sql->sql_select_array_query("SELECT count(*) as `total` FROM `movie` WHERE user_id = '{$user_id}'");
        return count($result) ? $result[0]['total'] : 0;
    }

    // Get highest source_stream_id for custom group
    function new_custom_source_stream_id ($user_id) {
        global $sql;
        $result = $sql->sql_select_array_query("SELECT source_stream_id FROM `movie` WHERE user_id = '{$user_id}' AND stream_is_custom = true ORDER BY source_stream_id DESC");
        return count($result) > 0 ? intval($result[0]['source_stream_id']) +1 : 1;
    }

    // Get movies that are now in the cinema
    function now_playing ($user_id, $playlist_id, $offset, $limit) {
        global $sql;
        return $sql->sql_select_array_query("SELECT *, CONCAT('http://', (SELECT source_host FROM playlist WHERE id = m.playlist_id), ':', (SELECT source_port FROM playlist WHERE id = m.playlist_id), '/movie/', (SELECT source_username FROM playlist WHERE id = m.playlist_id), '/', (SELECT source_password FROM playlist WHERE id = m.playlist_id), '/', m.source_stream_id, '.', m.source_container_extension) as 'xtream_url' FROM `movie` m WHERE tmdb_id in (SELECT tmdb_id FROM `tmdb_movies_cinema`) AND user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND tmdb <> '' AND tmdb IS NOT NULL GROUP BY tmdb_id LIMIT {$offset}, {$limit}");
    }

    // Get movies that are top rated
    function top_rated ($user_id, $playlist_id, $offset, $limit) {
        global $sql;
        return $sql->sql_select_array_query("SELECT *, CONCAT('http://', (SELECT source_host FROM playlist WHERE id = m.playlist_id), ':', (SELECT source_port FROM playlist WHERE id = m.playlist_id), '/movie/', (SELECT source_username FROM playlist WHERE id = m.playlist_id), '/', (SELECT source_password FROM playlist WHERE id = m.playlist_id), '/', m.source_stream_id, '.', m.source_container_extension) as 'xtream_url' FROM `movie` m WHERE tmdb_id in (SELECT tmdb_id FROM `tmdb_movies_top`) AND user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND tmdb <> '' AND tmdb IS NOT NULL GROUP BY tmdb_id LIMIT {$offset}, {$limit}");
    }

    // Get movies that are popular
    function popular ($user_id, $playlist_id, $offset, $limit) {
        global $sql;
        return $sql->sql_select_array_query("SELECT *, CONCAT('http://', (SELECT source_host FROM playlist WHERE id = m.playlist_id), ':', (SELECT source_port FROM playlist WHERE id = m.playlist_id), '/movie/', (SELECT source_username FROM playlist WHERE id = m.playlist_id), '/', (SELECT source_password FROM playlist WHERE id = m.playlist_id), '/', m.source_stream_id, '.', m.source_container_extension) as 'xtream_url' FROM `movie` m WHERE tmdb_id in (SELECT tmdb_id FROM `tmdb_movies_popular`) AND user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND tmdb <> '' AND tmdb IS NOT NULL GROUP BY tmdb_id LIMIT {$offset}, {$limit}");
    }

    // Get all movies
    function browse ($user_id, $playlist_id, $offset, $limit) {
        global $sql;
        return $sql->sql_select_array_query("SELECT *, CONCAT('http://', (SELECT source_host FROM playlist WHERE id = m.playlist_id), ':', (SELECT source_port FROM playlist WHERE id = m.playlist_id), '/movie/', (SELECT source_username FROM playlist WHERE id = m.playlist_id), '/', (SELECT source_password FROM playlist WHERE id = m.playlist_id), '/', m.source_stream_id, '.', m.source_container_extension) as 'xtream_url' FROM `movie` m WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND tmdb <> '' AND tmdb IS NOT NULL GROUP BY tmdb_id LIMIT {$offset}, {$limit}");
    }

    // Get total count of movies for Browsing
    function browse_total ($user_id, $playlist_id) {
        global $sql;
        $result = $sql->sql_select_array_query("SELECT count(*) as `total` FROM `movie` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND tmdb <> '' AND tmdb IS NOT NULL");
        return count($result) ? $result[0]['total'] : 0;
    }

    // Search movie
    function search ($user_id, $playlist_id, $search) {
        global $sql;
        $search = str_replace(' ', '%', urldecode($search));
        return $sql->sql_select_array_query("SELECT *, CONCAT('http://', (SELECT source_host FROM playlist WHERE id = m.playlist_id), ':', (SELECT source_port FROM playlist WHERE id = m.playlist_id), '/movie/', (SELECT source_username FROM playlist WHERE id = m.playlist_id), '/', (SELECT source_password FROM playlist WHERE id = m.playlist_id), '/', m.source_stream_id, '.', m.source_container_extension) as 'xtream_url' FROM `movie` m WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND tmdb <> '' AND tmdb IS NOT NULL AND movie_name LIKE '%{$search}%' GROUP BY tmdb_id");
    }

}