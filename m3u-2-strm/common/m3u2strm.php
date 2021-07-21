<?php

/**********************************************************************************/
/*																				  */
/*				m3u2strm.php 					  								  */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 18/07/2021									  		  */
/*				Version	: 1.0													  */
/*																				  */
/**********************************************************************************/

class m3u2strm {

	// User class constructor
	function __construct () {
		
    }
    
    // Authenticate user and get instances
    function authenticate ($username, $password) {
        global $sql;
        // Find user that matches this username and password
        $res = $sql->sql_select_array_query("SELECT * FROM `user` WHERE username = '{$username}' AND status = 2");
        if (count($res) >= 1) {
            $user    = $res[0];
            $user_id = $user['id'];
            // Compare the password
            if (hash('sha512', $password) === $user['password']) {
                $subscription = $sql->sql_select_array_query("SELECT `enabled`, UNIX_TIMESTAMP(end_date) as `end_date`, `subscription_type` FROM `subscription` WHERE user_id = '{$user_id}'")[0];
                if (!empty($subscription) && (is_null($subscription['end_date']) || $subscription['end_date'] > time()) && boolval($subscription['enabled']) === true) {
                    return $sql->sql_select_array_query("SELECT `id`, `user_id`, `name`, `file_naming_movies`, `movies_folder`, `file_naming_series`, `series_folder`, `create_nfo`, `overwrite_files`, `delete_removed`, `api_key` FROM `m3u2strm` WHERE user_id = '{$user_id}'");
                }
                return false;
            }
        }
        return false;
    }

    // Get total count of movies
    function total_movies ($api_key) {
        global $sql;
        $result = $sql->sql_select_array_query("SELECT count(*) as total FROM `movie` WHERE `user_id` = (SELECT user_id FROM `m3u2strm` WHERE BINARY api_key = '{$api_key}') AND `playlist_id` = (SELECT playlist_id FROM `m3u2strm` WHERE BINARY api_key = '{$api_key}') AND `sync_is_removed` = 0");
        return count($result) ? $result[0]['total'] : 0;
    }

    // Get movies
    function movies ($api_key, $offset, $limit) {
        global $sql;
        return $sql->sql_select_array_query("SELECT `source_stream_url`, `stream_is_custom`, `movie_name`, `movie_year`, `tmdb_id`, `tmdb`,, (SELECT group_name FROM groups WHERE id = m.group_id) as 'group_name', CONCAT('http://', (SELECT source_host FROM playlist WHERE id = m.playlist_id), ':', (SELECT source_port FROM playlist WHERE id = m.playlist_id), '/movie/', (SELECT source_username FROM playlist WHERE id = m.playlist_id), '/', (SELECT source_password FROM playlist WHERE id = m.playlist_id), '/', m.source_stream_id, '.', m.source_container_extension) as 'xtream_url' FROM `movie` m WHERE `user_id` = (SELECT user_id FROM `m3u2strm` WHERE BINARY api_key = '{$api_key}') AND `playlist_id` = (SELECT playlist_id FROM `m3u2strm` WHERE BINARY api_key = '{$api_key}') AND tmdb IS NOT NULL AND tmdb <> '' AND `sync_is_removed` = 0 LIMIT {$offset}, {$limit}");
    }

    // Get total count of series
    function total_series ($api_key) {
        global $sql;
        $result = $sql->sql_select_array_query("SELECT count(*) as total FROM `series_tmdb` WHERE `user_id` = (SELECT user_id FROM `m3u2strm` WHERE BINARY api_key = '{$api_key}') AND `playlist_id` = (SELECT playlist_id FROM `m3u2strm` WHERE BINARY api_key = '{$api_key}')");
        return count($result) ? $result[0]['total'] : 0;
    }

    // Get series
    function series ($api_key, $offset, $limit) {
        global $sql;
        return $sql->sql_select_array_query("SELECT `tmdb_id`, `tmdb` FROM `series_tmdb` WHERE `user_id` = (SELECT user_id FROM `m3u2strm` WHERE BINARY api_key = '{$api_key}') AND `playlist_id` = (SELECT playlist_id FROM `m3u2strm` WHERE BINARY api_key = '{$api_key}') GROUP BY tmdb_id LIMIT {$offset}, {$limit}");
    }

    // Get episodes for series
    function episodes ($api_key, $tmdb_id) {
        global $sql;
        return $sql->sql_select_array_query("SELECT `source_stream_url`, `stream_is_custom`, `tmdb_id`, `tmdb_episode_id`, `tmdb`, `serie_name`, `serie_year`, `serie_season`, `serie_episode`, `serie_trailer`, (SELECT group_name FROM groups WHERE id = s.group_id) as 'group_name', CONCAT('http://', (SELECT source_host FROM playlist WHERE id = s.playlist_id), ':', (SELECT source_port FROM playlist WHERE id = s.playlist_id), '/series/', (SELECT source_username FROM playlist WHERE id = s.playlist_id), '/', (SELECT source_password FROM playlist WHERE id = s.playlist_id), '/', s.source_stream_id, '.', s.source_container_extension) as 'xtream_url' FROM `episodes` s WHERE `user_id` = (SELECT user_id FROM `m3u2strm` WHERE BINARY api_key = '{$api_key}') AND `playlist_id` = (SELECT playlist_id FROM `m3u2strm` WHERE BINARY api_key = '{$api_key}') AND tmdb_id = '{$tmdb_id}' AND tmdb IS NOT NULL AND tmdb <> '' AND `sync_is_removed` = 0");
    }

}