<?php

/**********************************************************************************/
/*																				  */
/*				app.php 					  									  */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 05/08/2021									  		  */
/*				Version	: 1.0													  */
/*																				  */
/**********************************************************************************/

class app {
	private $server_key;

	// App class constructor
	function __construct () {
    }

    // Get totals (Movies, Series, Live)
    function totals ($user_id, $code) {
        global $sql;
        $groups = $sql->sql_select_array_query("SELECT groups FROM `app` WHERE `user_id` = '{$user_id}' AND `code` = '{$code}'");
        if (count($groups) == 1) {
            $groups = implode(',', $groups[0]['groups']);
            $totals = $sql->sql_select_array_query("SELECT count(*) as 'movies', (SELECT count(*) FROM `episodes` WHERE user_id = '{$user_id}' AND group_id in ({$groups})) as 'series', (SELECT count(*) FROM `live` WHERE user_id = '{$user_id}' AND group_id in ({$groups}) AND stream_radio = 0) as 'live', (SELECT count(*) FROM `live` WHERE user_id = '{$user_id}' AND group_id in ({$groups}) AND stream_radio = 1) as 'radio' FROM `movie` WHERE user_id = '{$user_id}' AND group_id in ({$groups})");
            if (count($totals) == 1) {
                return $totals[0];
            }
        }
        return [
            'movies' => 0,
            'series' => 0,
            'live'   => 0,
            'radio'  => 0
        ];
    }

    // Get XD-Pro instances
    function xdpro_instances ($user_id) {
        global $sql;
        return $sql->sql_select_array_query("SELECT * FROM `xdpro` WHERE user_id = '{$user_id}'");
    }

    // XD-Pro downloads
    function xdpro_downloads ($user_id) {
        global $sql;
        return $sql->sql_select_array_query("SELECT * FROM `xdpro_downloads` WHERE user_id = '{$user_id}'");
    }

    // Update XD-Pro download
    function update_xdpro_download ($user_id, $id, $data) {
        global $sql;
        if ($sql->sql_update('xdpro_downloads', $data, ['user_id' => $user_id, 'id' => $id])) {
            return true;
        } else {
            return $sql->sql_last_error();
        }
    }

    // Delete XD-Pro download
    function delete_xdpro_download ($user_id, $id) {
        global $sql;
        return $sql->sql_delete('xdpro_downloads', ['user_id' => $user_id, 'id' => $id]);
    }

    // Pause all downloads (not active)
    function pause_xdpro_downloads ($user_id) {
        global $sql;
        return $sql->sql_update('xdpro_downloads', ['enabled' => 0], ['user_id' => $user_id, 'active' => 0]);
    }

    // Resume all downloads
    function resume_xdpro_downloads ($user_id) {
        global $sql;
        return $sql->sql_update('xdpro_downloads', ['enabled' => 1], ['user_id' => $user_id, 'active' => 0]);
    }

    // Delete downloads
    function delete_xdpro_downloads ($user_id, $disabled = false) {
        global $sql;
        if ($disabled === true) {
            return $sql->sql_delete('xdpro_downloads', ['user_id' => $user_id, 'active' => 0, 'enabled' => 0]);
        } else {
            return $sql->sql_delete('xdpro_downloads', ['user_id' => $user_id, 'active' => 0]);
        }
    }

    // Search for Movies, Series and TV programmes
    function search ($user_id, $code, $search, $movies, $series, $catchup, $group) {
        global $sql;
        $result = [
            'movies'  => [],
            'series'  => [],
            'catchup' => []
        ];
        $search = '%' . str_replace(' ', '%', $search) . '%';
        $groups = implode(',', $sql->sql_select_array_query("SELECT groups FROM `app` WHERE `user_id` = '{$user_id}' AND `code` = '{$code}'")[0]['groups']);
        $group  = $group ? 'GROUP BY tmdb_id' : '';
        if ($movies === true) {
            $movies = $sql->sql_select_array_query("SELECT * FROM `movie` WHERE user_id = '{$user_id}' AND group_id IN ({$groups}) AND sync_is_removed = 0 AND (stream_tvg_name LIKE '{$search}' OR movie_name LIKE '{$search}' OR movie_year LIKE '{$search}' OR tmdb_keywords LIKE '{$search}') AND (tmdb <> '' AND tmdb IS NOT NULL) {$group} LIMIT 50");
            usort($movies, function($a, $b) {
                return strtotime($b['tmdb']['release_date']) - strtotime($a['tmdb']['release_date']);
            });
            $result['movies'] = $movies;
        }
        if ($series === true) {
            $series = $sql->sql_select_array_query("SELECT * FROM (SELECT `tmdb`, (SELECT count(*) FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id IN ({$groups})) AS `episodes`, (SELECT count(DISTINCT serie_season) FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id IN ({$groups})) AS `seasons`, FROM `series_tmdb` s WHERE user_id = '{$user_id}' AND tmdb_id IN (SELECT DISTINCT tmdb_id FROM `episodes` WHERE serie_name LIKE '{$search}' OR stream_tvg_name LIKE '{$search}') AND (SELECT count(*) FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id IN ({$groups})) > 0) AS series GROUP BY tmdb_id");
            usort($series, function($a, $b) {
                return strtotime($b['tmdb']['first_air_date']) - strtotime($a['tmdb']['first_air_date']);
            });
            $result['series'] = $series;
        }
        if ($catchup === true) {

        }
        return $result;
    }

}