<?php

/**********************************************************************************/
/*																				  */
/*				kodi.php 					  								      */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 22/07/2021									  		  */
/*				Version	: 1.0													  */
/*																				  */
/**********************************************************************************/

class kodi {

	// User class constructor
	function __construct () {
		
    }
    
    // Authenticate user and get instances
    function authenticate ($username, $password, $code) {
        global $sql;
        // Find user that matches this username and password
        $res = $sql->sql_select_array_query("SELECT * FROM `user` WHERE username = '{$username}' AND status = 2");
        if (count($res) >= 1) {
            $user    = $res[0];
            $user_id = $user['id'];
            // Compare the password
            if (hash('sha512', $password) === $user['password']) {
                $subscription = $sql->sql_select_array_query("SELECT `enabled`, UNIX_TIMESTAMP(end_date) as `end_date`, `subscription_type` FROM `subscription` WHERE user_id = '{$user_id}'")[0];
                $instance     = $sql->sql_select_array_query("SELECT `api_key` FROM `kodi` WHERE user_id = '{$user_id}' AND code = '{$code}'");
                if (!empty($subscription) && (is_null($subscription['end_date']) || $subscription['end_date'] > time()) && boolval($subscription['enabled']) === true && count($instance) == 1) {
                    return [
                       'end_date'          => $subscription['end_date'],
                       'subscription_type' => $subscription['subscription_type'],
                       'api_key'           => $instance[0]['api_key']
                    ];
                }
                return false;
            }
        }
        return false;
    }

    // Account Information
    function account_information ($api_key) {
        global $sql;
        return $sql->sql_select_array_query("SELECT subscription_type, playlist_type, UNIX_TIMESTAMP(created) as `start`, UNIX_TIMESTAMP(end_date) as `end`, (SELECT `name` FROM `kodi` WHERE api_key = '{$api_key}') as `name` FROM `subscription` WHERE user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') LIMIT 1")[0];
    }

    // Movies - Now in theaters
    function movies_now_in_theaters ($api_key) {
        global $sql;
        $groups = $sql->sql_select_array_query("SELECT groups FROM `kodi` WHERE api_key = '{$api_key}'");
        if (count($groups) == 1) {
            $groups = implode(',', $groups[0]['groups']);
            $movies =  $sql->sql_select_array_query("SELECT * FROM (SELECT `id`, UNIX_TIMESTAMP(created_at) as `added`, `stream_tvg_logo`, `source_container_extension`, `tmdb_id`, `tmdb`, `tmdb_keywords`, `tmdb_credits`, `tmdb_similar`, `tmdb_videos`, (SELECT api_password FROM playlist WHERE user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND id = m.playlist_id) as 'password' FROM `movie` m WHERE tmdb_id in (SELECT tmdb_id FROM `tmdb_movies_cinema`) AND user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND group_id in ({$groups}) AND tmdb <> '' AND tmdb IS NOT NULL AND `sync_is_removed` = 0 ORDER BY `movie_year`) as movies GROUP BY tmdb_id LIMIT 0, 250");
            foreach ($movies as $index => $movie) {
                if (empty($movie['tmdb'])) {
                    unset($movies[$index]);
                }
            }
            usort($movies, function($a, $b) {
                if (isset($b['tmdb']['release_date']) && isset($a['tmdb']['release_date'])) {
                    return strtotime($b['tmdb']['release_date']) - strtotime($a['tmdb']['release_date']);
                } else {
                    return 0;
                }
            });
            return $movies;
        }
        return [];
    }

    // Movies - Top Rated
    function movies_top_rated ($api_key) {
        global $sql;
        $groups = $sql->sql_select_array_query("SELECT groups FROM `kodi` WHERE api_key = '{$api_key}'");
        if (count($groups) == 1) {
            $groups = implode(',', $groups[0]['groups']);
            $movies = $sql->sql_select_array_query("SELECT * FROM (SELECT `id`, UNIX_TIMESTAMP(created_at) as `added`, `stream_tvg_logo`, `source_container_extension`, `tmdb_id`, `tmdb`, `tmdb_keywords`, `tmdb_credits`, `tmdb_similar`, `tmdb_videos`, (SELECT api_password FROM playlist WHERE user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND id = m.playlist_id) as 'password' FROM `movie` m WHERE tmdb_id in (SELECT tmdb_id FROM `tmdb_movies_top`) AND user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND group_id in ({$groups})  AND `sync_is_removed` = 0 AND tmdb <> '' AND tmdb IS NOT NULL ORDER BY `movie_year`) as movies GROUP BY tmdb_id LIMIT 0, 250");
            foreach ($movies as $index => $movie) {
                if (empty($movie['tmdb'])) {
                    unset($movies[$index]);
                }
            }
            usort($movies, function($a, $b) {
                if (isset($b['tmdb']['release_date']) && isset($a['tmdb']['release_date'])) {
                    return strtotime($b['tmdb']['release_date']) - strtotime($a['tmdb']['release_date']);
                } else {
                    return 0;
                }
            });
            return $movies;
        }
        return [];
    }

    // Movies - Popular
    function movies_popular ($api_key) {
        global $sql;
        $groups = $sql->sql_select_array_query("SELECT groups FROM `kodi` WHERE api_key = '{$api_key}'");
        if (count($groups) == 1) {
            $groups = implode(',', $groups[0]['groups']);
            $movies = $sql->sql_select_array_query("SELECT * FROM (SELECT `id`, UNIX_TIMESTAMP(created_at) as `added`, `stream_tvg_logo`, `source_container_extension`, `tmdb_id`, `tmdb`, `tmdb_keywords`, `tmdb_credits`, `tmdb_similar`, `tmdb_videos`, (SELECT api_password FROM playlist WHERE user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND id = m.playlist_id) as 'password' FROM `movie` m WHERE tmdb_id in (SELECT tmdb_id FROM `tmdb_movies_popular`) AND user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND group_id in ({$groups})  AND `sync_is_removed` = 0 AND tmdb <> '' AND tmdb IS NOT NULL ORDER BY `movie_year`) as movies GROUP BY tmdb_id LIMIT 0, 250");
            foreach ($movies as $index => $movie) {
                if (empty($movie['tmdb'])) {
                    unset($movies[$index]);
                }
            }
            usort($movies, function($a, $b) {
                if (isset($b['tmdb']['release_date']) && isset($a['tmdb']['release_date'])) {
                    return strtotime($b['tmdb']['release_date']) - strtotime($a['tmdb']['release_date']);
                } else {
                    return 0;
                }
            });
            return $movies;
        }
        return [];
    }

    // Movies - Search
    function movies_search ($api_key, $search) {
        global $sql;
        $groups = $sql->sql_select_array_query("SELECT groups FROM `kodi` WHERE api_key = '{$api_key}'");
        if (count($groups) == 1) {
            $groups = implode(',', $groups[0]['groups']);
            $movies = $sql->sql_select_array_query("SELECT * FROM (SELECT `id`, UNIX_TIMESTAMP(created_at) as `added`, `stream_tvg_logo`, `source_container_extension`, `tmdb_id`, `tmdb`, `tmdb_keywords`, `tmdb_credits`, `tmdb_similar`, `tmdb_videos`, (SELECT api_password FROM playlist WHERE user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND id = m.playlist_id) as 'password' FROM `movie` m WHERE `movie_name` LIKE '%{$search}%' OR `stream_tvg_name` LIKE '%{$search}%' AND user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND group_id in ({$groups})  AND `sync_is_removed` = 0 AND tmdb <> '' AND tmdb IS NOT NULL ORDER BY `movie_year`) as movies GROUP BY tmdb_id LIMIT 0, 250");
            foreach ($movies as $index => $movie) {
                if (empty($movie['tmdb'])) {
                    unset($movies[$index]);
                }
            }
            usort($movies, function($a, $b) {
                if (isset($b['tmdb']['release_date']) && isset($a['tmdb']['release_date'])) {
                    return strtotime($b['tmdb']['release_date']) - strtotime($a['tmdb']['release_date']);
                } else {
                    return 0;
                };
            });
            return $movies;
        }
        return [];
    }

    // Movies - New
    function movies_new ($api_key) {
        global $sql;
        $groups = $sql->sql_select_array_query("SELECT groups FROM `kodi` WHERE api_key = '{$api_key}'");
        if (count($groups) == 1) {
            $groups = implode(',', $groups[0]['groups']);
            $movies = $sql->sql_select_array_query("SELECT * FROM (SELECT `id`, UNIX_TIMESTAMP(created_at) as `added`, `stream_tvg_logo`, `source_container_extension`, `tmdb_id`, `tmdb`, `tmdb_keywords`, `tmdb_credits`, `tmdb_similar`, `tmdb_videos`, (SELECT api_password FROM playlist WHERE user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND id = m.playlist_id) as 'password' FROM `movie` m WHERE `sync_is_new` = 1 AND user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND group_id in ({$groups}) AND `sync_is_removed` = 0 AND tmdb <> '' AND tmdb IS NOT NULL ORDER BY `movie_year`) as movies GROUP BY tmdb_id LIMIT 0, 250");
            foreach ($movies as $index => $movie) {
                if (empty($movie['tmdb'])) {
                    unset($movies[$index]);
                }
            }
            usort($movies, function($a, $b) {
                if (isset($b['tmdb']['release_date']) && isset($a['tmdb']['release_date'])) {
                    return strtotime($b['tmdb']['release_date']) - strtotime($a['tmdb']['release_date']);
                } else {
                    return 0;
                }
            });
            return $movies;
        }
        return [];
    }

    // Movies groups (Browse)
    function movies_groups ($api_key) {
        global $sql;
        $groups = $sql->sql_select_array_query("SELECT groups FROM `kodi` WHERE api_key = '{$api_key}'");
        if (count($groups) == 1) {
            $groups = implode(',', $groups[0]['groups']);
            return $sql->sql_select_array_query("SELECT `id`, `group_name` FROM `groups` WHERE user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND id in ({$groups}) AND group_type = 2 ORDER BY `playlist_id` ASC, `group_order` ASC");
        }
        return [];
    }

    // Movies - Browse (Group)
    function movies_browse_group ($api_key, $group_id) {
        global $sql;
        $movies = $sql->sql_select_array_query("SELECT * FROM (SELECT `id`, UNIX_TIMESTAMP(created_at) as `added`, `stream_tvg_logo`, `source_container_extension`, `tmdb_id`, `tmdb`, `tmdb_keywords`, `tmdb_credits`, `tmdb_similar`, `tmdb_videos`, (SELECT api_password FROM playlist WHERE user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND id = m.playlist_id) as 'password' FROM `movie` m WHERE user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND group_id = '{$group_id}' AND `sync_is_removed` = 0 AND tmdb <> '' AND tmdb IS NOT NULL ORDER BY `movie_year`) as movies GROUP BY tmdb_id");
        foreach ($movies as $index => $movie) {
            if (empty($movie['tmdb'])) {
                unset($movies[$index]);
            }
        }
        usort($movies, function($a, $b) {
            return strtotime($b['tmdb']['release_date']) - strtotime($a['tmdb']['release_date']);
        });
        return $movies;
    }

    // Series groups (Browse)
    function series_groups ($api_key) {
        global $sql;
        $groups = $sql->sql_select_array_query("SELECT groups FROM `kodi` WHERE api_key = '{$api_key}'");
        if (count($groups) == 1) {
            $groups = implode(',', $groups[0]['groups']);
            return $sql->sql_select_array_query("SELECT `id`, `group_name` FROM `groups` WHERE user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND id in ({$groups}) AND group_type = 3 ORDER BY `playlist_id` ASC, `group_order` ASC");
        }
        return '';
    }

    // Series - Browse (Group)
    function series_browse_group ($api_key, $group_id) {
        global $sql;
        $groups = $sql->sql_select_array_query("SELECT groups FROM `kodi` WHERE api_key = '{$api_key}'");
        if (count($groups) == 1) {
            $groups = implode(',', $groups[0]['groups']);
            $series = $sql->sql_select_array_query("SELECT `playlist_id`, `tmdb_id`, `tmdb`, `credits`, `keywords`, `videos`, (SELECT stream_tvg_logo FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id in ({$groups}) LIMIT 1) as `stream_tvg_logo`, (SELECT count(*) FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id in ({$groups})) as `episodes`, (SELECT count(DISTINCT serie_season) FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id in ({$groups})) as `seasons`, (SELECT api_password FROM playlist WHERE user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND id = s.playlist_id) as 'password' FROM `series_tmdb` s WHERE user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND (SELECT count(*) FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id = '{$group_id}') > 0");
            usort($series, function($a, $b) {
                if (isset($b['tmdb']['first_air_date']) && isset($a['tmdb']['first_air_date'])) {
                    return strtotime($b['tmdb']['first_air_date']) - strtotime($a['tmdb']['first_air_date']);
                } else {
                    return 0;
                }
            });
            return $series;
        }
        return [];
    }

    // Series - Now on TV
    function series_now_on_tv ($api_key) {
        global $sql;
        $groups = $sql->sql_select_array_query("SELECT groups FROM `kodi` WHERE api_key = '{$api_key}'");
        if (count($groups) == 1) {
            $groups = implode(',', $groups[0]['groups']);
            $series = $sql->sql_select_array_query("SELECT * FROM (SELECT `playlist_id`, `tmdb_id`, `tmdb`, `credits`, `keywords`, `videos`, (SELECT stream_tvg_logo FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id in ({$groups}) LIMIT 1) as `stream_tvg_logo`, (SELECT count(*) FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id in ({$groups})) as `episodes`, (SELECT count(DISTINCT serie_season) FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id in ({$groups})) as `seasons`, (SELECT api_password FROM playlist WHERE user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND id = s.playlist_id) as 'password' FROM `series_tmdb` s WHERE user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND tmdb_id in (SELECT tmdb_id FROM `tmdb_series_tv`) AND (SELECT count(*) FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id in ({$groups})) > 0) as series GROUP BY tmdb_id LIMIT 0, 250");
            usort($series, function($a, $b) {
                if (isset($b['tmdb']['first_air_date']) && isset($a['tmdb']['first_air_date'])) {
                    return strtotime($b['tmdb']['first_air_date']) - strtotime($a['tmdb']['first_air_date']);
                } else {
                    return 0;
                }
            });
            return $series;
        }
        return [];
    }

    // Series - Top Rated
    function series_top_rated ($api_key) {
        global $sql;
        $groups = $sql->sql_select_array_query("SELECT groups FROM `kodi` WHERE api_key = '{$api_key}'");
        if (count($groups) == 1) {
            $groups = implode(',', $groups[0]['groups']);
            $series = $sql->sql_select_array_query("SELECT * FROM (SELECT `playlist_id`, `tmdb_id`, `tmdb`, `credits`, `keywords`, `videos`, (SELECT stream_tvg_logo FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id in ({$groups}) LIMIT 1) as `stream_tvg_logo`, (SELECT count(*) FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id in ({$groups})) as `episodes`, (SELECT count(DISTINCT serie_season) FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id in ({$groups})) as `seasons`, (SELECT api_password FROM playlist WHERE user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND id = s.playlist_id) as 'password' FROM `series_tmdb` s WHERE user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND tmdb_id in (SELECT tmdb_id FROM `tmdb_series_top`) AND (SELECT count(*) FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id in ({$groups})) > 0) as series GROUP BY tmdb_id LIMIT 0, 250");
            usort($series, function($a, $b) {
                if (isset($b['tmdb']['first_air_date']) && isset($a['tmdb']['first_air_date'])) {
                    return strtotime($b['tmdb']['first_air_date']) - strtotime($a['tmdb']['first_air_date']);
                } else {
                    return 0;
                }
            });
            return $series;
        }
        return [];
    }

    // Series - Popular
    function series_popular ($api_key) {
        global $sql;
        $groups = $sql->sql_select_array_query("SELECT groups FROM `kodi` WHERE api_key = '{$api_key}'");
        if (count($groups) == 1) {
            $groups = implode(',', $groups[0]['groups']);
            $series = $sql->sql_select_array_query("SELECT * FROM (SELECT `playlist_id`, `tmdb_id`, `tmdb`, `credits`, `keywords`, `videos`, (SELECT stream_tvg_logo FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id in ({$groups}) LIMIT 1) as `stream_tvg_logo`, (SELECT count(*) FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id in ({$groups})) as `episodes`, (SELECT count(DISTINCT serie_season) FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id in ({$groups})) as `seasons`, (SELECT api_password FROM playlist WHERE user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND id = s.playlist_id) as 'password' FROM `series_tmdb` s WHERE user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND tmdb_id in (SELECT tmdb_id FROM `tmdb_series_popular`) AND (SELECT count(*) FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id in ({$groups})) > 0) as series GROUP BY tmdb_id LIMIT 0, 250");
            usort($series, function($a, $b) {
                if (isset($b['tmdb']['first_air_date']) && isset($a['tmdb']['first_air_date'])) {
                    return strtotime($b['tmdb']['first_air_date']) - strtotime($a['tmdb']['first_air_date']);
                } else {
                    return 0;
                }
            });
            return $series;
        }
        return [];
    }

    // Series - Search
    function series_search ($api_key, $search) {
        global $sql;
        $groups = $sql->sql_select_array_query("SELECT groups FROM `kodi` WHERE api_key = '{$api_key}'");
        if (count($groups) == 1) {
            $groups = implode(',', $groups[0]['groups']);
            $series = $sql->sql_select_array_query("SELECT * FROM (SELECT `playlist_id`, `tmdb_id`, `tmdb`, `credits`, `keywords`, `videos`, (SELECT stream_tvg_logo FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id in ({$groups}) LIMIT 1) as `stream_tvg_logo`, (SELECT count(*) FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id in ({$groups})) as `episodes`, (SELECT count(DISTINCT serie_season) FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id in ({$groups})) as `seasons`, (SELECT api_password FROM playlist WHERE user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND id = s.playlist_id) as 'password' FROM `series_tmdb` s WHERE user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND tmdb_id in (SELECT DISTINCT tmdb_id FROM `episodes` WHERE serie_name LIKE '%{$search}%' OR stream_tvg_name LIKE '%{$search}%') AND (SELECT count(*) FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id in ({$groups})) > 0) as series GROUP BY tmdb_id LIMIT 0, 250");
            usort($series, function($a, $b) {
                if (isset($b['tmdb']['first_air_date']) && isset($a['tmdb']['first_air_date'])) {
                    return strtotime($b['tmdb']['first_air_date']) - strtotime($a['tmdb']['first_air_date']);
                } else {
                    return 0;
                }
            });
            return $series;
        }
        return [];
    }

    // Series - New
    function series_new ($api_key) {
        global $sql;
        $groups = $sql->sql_select_array_query("SELECT groups FROM `kodi` WHERE api_key = '{$api_key}'");
        if (count($groups) == 1) {
            $groups = implode(',', $groups[0]['groups']);
            $series = $sql->sql_select_array_query("SELECT * FROM (SELECT `playlist_id`, `tmdb_id`, `tmdb`, `credits`, `keywords`, `videos`, (SELECT stream_tvg_logo FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id in ({$groups}) LIMIT 1) as `stream_tvg_logo`, (SELECT count(*) FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id in ({$groups})) as `episodes`, (SELECT count(DISTINCT serie_season) FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id in ({$groups})) as `seasons`, (SELECT api_password FROM playlist WHERE user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND id = s.playlist_id) as 'password' FROM `series_tmdb` s WHERE user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND tmdb_id in (SELECT DISTINCT tmdb_id FROM `episodes` WHERE sync_is_new = 1 AND user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}')) AND (SELECT count(*) FROM `episodes` WHERE tmdb_id = s.tmdb_id AND playlist_id = s.playlist_id AND group_id in ({$groups})) > 0) as series GROUP BY tmdb_id LIMIT 0, 250");
            usort($series, function($a, $b) {
                if (isset($b['tmdb']['first_air_date']) && isset($a['tmdb']['first_air_date'])) {
                    return strtotime($b['tmdb']['first_air_date']) - strtotime($a['tmdb']['first_air_date']);
                } else {
                    return 0;
                }
            });
            return $series;
        }
        return [];
    }

    // Series - seasons
    function series_seasons ($api_key, $playlist_id, $tmdb_id) {
        global $sql;
        $groups = $sql->sql_select_array_query("SELECT groups FROM `kodi` WHERE api_key = '{$api_key}'");
        if (count($groups) == 1) {
            $groups = implode(',', $groups[0]['groups']);
            return $sql->sql_select_array_query("SELECT DISTINCT serie_season FROM `episodes` where user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') and playlist_id = '{$playlist_id}' and tmdb_id = '{$tmdb_id}' ORDER BY serie_season ASC");
        }
        return [];
    }

    // Series - Episodes
    function series_episodes ($api_key, $playlist_id, $tmdb_id, $season) {
        global $sql;
        $groups = $sql->sql_select_array_query("SELECT groups FROM `kodi` WHERE api_key = '{$api_key}'");
        if (count($groups) == 1) {
            $groups = implode(',', $groups[0]['groups']);
            return $sql->sql_select_array_query("SELECT * FROM `episodes` WHERE user_id = (SELECT user_id FROM `kodi` WHERE api_key = '{$api_key}') AND playlist_id = '{$playlist_id}' AND tmdb_id = '{$tmdb_id}' AND serie_season = '{$season}' ORDER BY serie_episode ASC");
        }
        return [];
    }

}