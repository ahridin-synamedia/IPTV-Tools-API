<?php

/**********************************************************************************/
/*																				  */
/*				serie.php 				  			    					      */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 30/05/2021									  		  */
/*				Version	: 1.0													  */
/*																				  */
/**********************************************************************************/

class serie {
    
    // Serie class constructor
	function __construct () {
		
    }

    // Get series streams for user
    function get ($user_id, $from, $amount, $simple = false) {
        global $sql;
        if ($simple === true) {
            return $sql->sql_select_array_query("SELECT `id`, `stream_tvg_name`, `source_tvg_name` FROM `episodes` WHERE user_id = '{$user_id}'");
        }
        return $sql->sql_select_array_query("SELECT `id`, `user_id`, `playlist_id`, `group_id`, `stream_tvg_name`, `stream_tvg_id`, `stream_tvg_logo`, `stream_tvg_chno`, `stream_tvg_shift`, `stream_parent_code`, `stream_audio_track`, `stream_aspect_ratio`, `stream_order`, `stream_is_hidden`, `sync_is_new`, `sync_is_removed`, `serie_name`, `serie_season`, `serie_episode`, `serie_trailer`, `tmdb_id`, `tmdb_episode_id`, (SELECT name FROM `playlist` WHERE `id` = s.playlist_id) AS `playlist`, (SELECT group_name FROM `groups` WHERE `id` = s.group_id) AS `group` FROM `episodes` s WHERE user_id = '{$user_id}' LIMIT {$from}, {$amount}");
    }

    // Add new empty series stream
    function add ($user_id) {
        global $sql;
        $source_stream_id = $this->new_custom_source_stream_id($user_id);
        if ($sql->sql_insert('episodes', ['user_id' => $user_id, 'source_stream_id' => $source_stream_id, 'stream_is_custom' => true])) {
            return $sql->last_insert_id();
        } else {
            return $sql->sql_last_error();
        }
    }

    // Update series stream
    function update ($user_id, $stream_id, $stream) {
        global $sql;
        if ($sql->sql_update('episodes', $stream, ['user_id' => $user_id, 'id' => $stream_id])) {
            return true;
        } else {
            return $sql->sql_last_error();
        }
    }

    // Delete series stream
    function delete ($user_id, $stream_id) {
        global $sql;
        return $sql->sql_delete('episodes', ['user_id' => $user_id, 'id' => $stream_id]);
    }

    // Get count of streams
    function total ($user_id) {
        global $sql;
        $result = $sql->sql_select_array_query("SELECT count(*) as `total` FROM `episodes` WHERE user_id = '{$user_id}'");
        return count($result) ? $result[0]['total'] : 0;
    }

    // Get highest source_stream_id for custom group
    function new_custom_source_stream_id ($user_id) {
        global $sql;
        $result = $sql->sql_select_array_query("SELECT source_stream_id FROM `episodes` WHERE user_id = '{$user_id}' AND stream_is_custom = true ORDER BY source_stream_id DESC");
        return count($result) > 0 ? intval($result[0]['source_stream_id']) +1 : 1;
    }

    // Get series details and episodes for series
    function details ($user_id, $playlist_id, $tmdb_id) {
        global $sql;
        $serie = $sql->sql_select_array_query("SELECT * FROM `series_tmdb` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND tmdb_id = '{$tmdb_id}'");
        return [
            'credits'  => $serie[0]['credits'],
            'keywords' => $serie[0]['keywords'],
            'similar'  => $serie[0]['similar'],
            'videos'   => $serie[0]['videos'],
            'episodes' => $sql->sql_select_array_query("SELECT `source_stream_url`, `source_container_extension`, `tmdb_episode_id`, `tmdb`, `serie_name`, `serie_season`, `serie_episode`, `serie_trailer`, CONCAT('http://', (SELECT source_host FROM playlist WHERE id = s.playlist_id), ':', (SELECT source_port FROM playlist WHERE id = s.playlist_id), '/series/', (SELECT source_username FROM playlist WHERE id = s.playlist_id), '/', (SELECT source_password FROM playlist WHERE id = s.playlist_id), '/', s.source_stream_id, '.', s.source_container_extension) as 'xtream_url' FROM `episodes` s WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND tmdb_id = '{$tmdb_id}'")
        ];
    }

    // Get series that are now on TV
    function now_on_tv ($user_id, $playlist_id, $offset, $limit) {
        global $sql;
        $series = $sql->sql_select_array_query("SELECT * FROM `series_tmdb` s WHERE tmdb_id in (SELECT tmdb_id FROM `tmdb_series_tv`) AND user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' GROUP BY tmdb_id LIMIT {$offset}, {$limit}");
        foreach ($series as &$serie) {
            $tmdb_id = $serie['tmdb_id']; 
            $result  = $sql->sql_select_array_query("SELECT count(DISTINCT serie_season) as 'seasons', count(serie_episode) as 'episodes' FROM `episodes` WHERE user_id = '{$user_id}' AND tmdb_id = '{$tmdb_id}' LIMIT 1");
            $serie['episodes'] = $result[0]['episodes'];
            $serie['seasons'] = $result[0]['seasons'];
        }
        return $series;
    }

    // Get series that are popular at the moment
    function popular ($user_id, $playlist_id, $offset, $limit) {
        global $sql;
        $series = $sql->sql_select_array_query("SELECT * FROM `series_tmdb` s WHERE tmdb_id in (SELECT tmdb_id FROM `tmdb_series_popular`) AND user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' GROUP BY tmdb_id LIMIT {$offset}, {$limit}");
        foreach ($series as &$serie) {
            $tmdb_id = $serie['tmdb_id']; 
            $result  = $sql->sql_select_array_query("SELECT count(DISTINCT serie_season) as 'seasons', count(serie_episode) as 'episodes' FROM `episodes` WHERE user_id = '{$user_id}' AND tmdb_id = '{$tmdb_id}' LIMIT 1");
            $serie['episodes'] = $result[0]['episodes'];
            $serie['seasons'] = $result[0]['seasons'];
        }
        return $series;
    }

    // Get series that are rated as top
    function top_rated ($user_id, $playlist_id, $offset, $limit) {
        global $sql;
        $series = $sql->sql_select_array_query("SELECT * FROM `series_tmdb` s WHERE tmdb_id in (SELECT tmdb_id FROM `tmdb_series_top`) AND user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' GROUP BY tmdb_id LIMIT {$offset}, {$limit}");
        foreach ($series as &$serie) {
            $tmdb_id = $serie['tmdb_id']; 
            $result  = $sql->sql_select_array_query("SELECT count(DISTINCT serie_season) as 'seasons', count(serie_episode) as 'episodes' FROM `episodes` WHERE user_id = '{$user_id}' AND tmdb_id = '{$tmdb_id}' LIMIT 1");
            $serie['episodes'] = $result[0]['episodes'];
            $serie['seasons'] = $result[0]['seasons'];
        }
        return $series;
    }

    // Get all series
    function browse ($user_id, $playlist_id, $offset, $limit) {
        global $sql;
        $series = $sql->sql_select_array_query("SELECT * FROM `series_tmdb` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' GROUP BY tmdb_id LIMIT {$offset}, {$limit}");
        foreach ($series as &$serie) {
            $tmdb_id = $serie['tmdb_id']; 
            $result  = $sql->sql_select_array_query("SELECT count(DISTINCT serie_season) as 'seasons', count(serie_episode) as 'episodes' FROM `episodes` WHERE user_id = '{$user_id}' AND tmdb_id = '{$tmdb_id}' LIMIT 1");
            $serie['episodes'] = $result[0]['episodes'];
            $serie['seasons'] = $result[0]['seasons'];
        }
        return $series;
    }

    // Count total series
    function browse_total ($user_id, $playlist_id) {
        global $sql;
        $result = $sql->sql_select_array_query("SELECT count(*) as `total` FROM `series_tmdb` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND tmdb <> '' AND tmdb IS NOT NULL");
        return count($result) ? $result[0]['total'] : 0;
    }

    // Search series
    function search ($user_id, $playlist_id, $search) {
        global $sql;
        $search = str_replace(' ', '%', urldecode($search));
        $series = $sql->sql_select_array_query("SELECT * FROM `series_tmdb` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND tmdb LIKE '%{$search}%' GROUP BY tmdb_id");
        foreach ($series as &$serie) {
            $tmdb_id = $serie['tmdb_id']; 
            $result  = $sql->sql_select_array_query("SELECT count(DISTINCT serie_season) as 'seasons', count(serie_episode) as 'episodes' FROM `episodes` WHERE user_id = '{$user_id}' AND tmdb_id = '{$tmdb_id}' LIMIT 1");
            $serie['episodes'] = $result[0]['episodes'];
            $serie['seasons'] = $result[0]['seasons'];
        }
        return $series;
    }

}