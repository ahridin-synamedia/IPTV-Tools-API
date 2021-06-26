<?php

/**********************************************************************************/
/*																				  */
/*				xmltv.php 				  									      */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 20/05/2021									  		  */
/*				Version	: 1.0													  */
/*																				  */
/**********************************************************************************/

class xmltv {

    // XMLTV Class constructor
	function __construct () {
		
    }

    // Get xtream accounts
    function xtream ($user_id) {
        global $sql;
        return $sql->sql_select_array_query("SELECT * FROM `source_xtream` WHERE user_id = '{$user_id}' ORDER BY id ASC");
    }

    // Delete xtream account
    function delete_xtream ($user_id, $id) {
        global $sql;
        $source_xtream_id = $id;
        $categories = $sql->sql_select_array_query("SELECT id FROM `category_xtream` WHERE user_id = '{$user_id}' AND source_xtream_id = '{$source_xtream_id}'");
        foreach ($categories as $category) {
            $category_xtream_id = $category['id'];
            // Remove Live
            $sql->sql_query("DELETE FROM `stream_xtream_live` WHERE user_id = '{$user_id}' AND source_xtream_id = '{$source_xtream_id}' AND category_xtream_id = '{$category_xtream_id}'");
            // Remove Movie
            $sql->sql_query("DELETE FROM `stream_xtream_movies` WHERE user_id = '{$user_id}' AND source_xtream_id = '{$source_xtream_id}' AND category_xtream_id = '{$category_xtream_id}'");
            // Remove Series
            $sql->sql_query("DELETE FROM `stream_xtream_series` WHERE user_id = '{$user_id}' AND source_xtream_id = '{$source_xtream_id}' AND category_xtream_id = '{$category_xtream_id}'");
            // Remove Category
            $sql->sql_query("DELETE FROM `category_xtream` WHERE user_id = '{$user_id}' AND source_xtream_id = '{$source_xtream_id}'");
        }
        // Remove serie data for removed series
        $sql->sql_query("DELETE FROM series_tmdb WHERE tmdb_id NOT IN (SELECT tmdb_id from stream_m3u_file_series where user_id = '{$user_id}' UNION SELECT tmdb_id from stream_m3u_url_series where user_id = '{$user_id}' UNION SELECT tmdb_id from stream_xtream_series where user_id = '{$user_id}') AND user_id = '{$user_id}'");
        return $sql->sql_delete('source_xtream', ['user_id' => $user_id, 'id' => $id]);
    }

    // Add xtream account
    function add_xtream ($account) {
        global $sql;
        return $sql->sql_insert('source_xtream', $account);
    }

    // Update xtream account
    function update_xtream ($user_id, $account) {
        global $sql;
        return $sql->sql_update('source_xtream', $account, ['user_id' => $user_id, 'id' => $account['id']]);
    }

    // Get m3u urls
    function m3u_url ($user_id) {
        global $sql;
        return $sql->sql_select_array_query("SELECT * FROM `source_m3u_url` WHERE user_id = '{$user_id}' ORDER BY id ASC");
    }

    // Delete m3u url
    function delete_m3u_url ($user_id, $id) {
        global $sql;
        $source_m3u_url_id = $id;
        $categories = $sql->sql_select_array_query("SELECT id FROM `category_m3u_url` WHERE user_id = '{$user_id}' AND source_m3u_url_id = '{$source_m3u_url_id}'");
        foreach ($categories as $category) {
            $category_m3u_url_id = $category['id'];
            // Remove Live
            $sql->sql_query("DELETE FROM `stream_m3u_url_live` WHERE user_id = '{$user_id}' AND source_m3u_url_id = '{$source_m3u_url_id}' AND category_m3u_url_id = '{$category_m3u_url_id}'");
            // Remove Movie
            $sql->sql_query("DELETE FROM `stream_m3u_url_movies` WHERE user_id = '{$user_id}' AND source_m3u_url_id = '{$source_m3u_url_id}' AND category_m3u_url_id = '{$category_m3u_url_id}'");
            // Remove Series
            $sql->sql_query("DELETE FROM `stream_m3u_url_series` WHERE user_id = '{$user_id}' AND source_m3u_url_id = '{$source_m3u_url_id}' AND category_m3u_url_id = '{$category_m3u_url_id}'");
            // Remove Category
            $sql->sql_query("DELETE FROM `category_m3u_url` WHERE user_id = '{$user_id}' AND source_m3u_url_id = '{$source_m3u_url_id}'");
        }
        // Remove serie data for removed series
        $sql->sql_query("DELETE FROM series_tmdb WHERE tmdb_id NOT IN (SELECT tmdb_id from stream_m3u_file_series where user_id = '{$user_id}' UNION SELECT tmdb_id from stream_m3u_url_series where user_id = '{$user_id}' UNION SELECT tmdb_id from stream_xtream_series where user_id = '{$user_id}') AND user_id = '{$user_id}'");

        return $sql->sql_delete('source_m3u_url', ['user_id' => $user_id, 'id' => $id]);
    }

    // Add m3u url
    function add_m3u_url ($playlist) {
        global $sql;
        return $sql->sql_insert('source_m3u_url', $playlist);
    }

    // Update m3u url
    function update_m3u_url ($user_id, $playlist) {
        global $sql;
        return $sql->sql_update('source_m3u_url', $playlist, ['user_id' => $user_id, 'id' => $playlist['id']]);
    }

    // Get m3u files
    function m3u_file ($user_id) {
        global $sql;
        return $sql->sql_select_array_query("SELECT * FROM `source_m3u_file` WHERE user_id = '{$user_id}' ORDER BY id ASC");
    }

    // Delete m3u file
    function delete_m3u_file ($user_id, $id) {
        global $sql;
        $source_m3u_file_id = $id;
        $categories = $sql->sql_select_array_query("SELECT id FROM `category_m3u_file` WHERE user_id = '{$user_id}' AND source_m3u_file_id = '{$source_m3u_file_id}'");
        foreach ($categories as $category) {
            $category_m3u_file_id = $category['id'];
            // Remove Live
            $sql->sql_query("DELETE FROM `stream_m3u_file_live` WHERE user_id = '{$user_id}' AND source_m3u_file_id = '{$source_m3u_file_id}' AND category_m3u_file_id = '{$category_m3u_file_id}'");
            // Remove Movie
            $sql->sql_query("DELETE FROM `stream_m3u_file_movies` WHERE user_id = '{$user_id}' AND source_m3u_file_id = '{$source_m3u_file_id}' AND category_m3u_file_id = '{$category_m3u_file_id}'");
            // Remove Series
            $sql->sql_query("DELETE FROM `stream_m3u_file_series` WHERE user_id = '{$user_id}' AND source_m3u_file_id = '{$source_m3u_file_id}' AND category_m3u_file_id = '{$category_m3u_file_id}'");
            // Remove Category
            $sql->sql_query("DELETE FROM `category_m3u_file` WHERE user_id = '{$user_id}' AND source_m3u_file_id = '{$source_m3u_file_id}'");
        }
        // Remove serie data for removed series
        $sql->sql_query("DELETE FROM series_tmdb WHERE tmdb_id NOT IN (SELECT tmdb_id from stream_m3u_file_series where user_id = '{$user_id}' UNION SELECT tmdb_id from stream_m3u_url_series where user_id = '{$user_id}' UNION SELECT tmdb_id from stream_xtream_series where user_id = '{$user_id}') AND user_id = '{$user_id}'");
        return $sql->sql_delete('source_m3u_file', ['user_id' => $user_id, 'id' => $id]);
    }

    // Add m3u file
    function add_m3u_file ($user_id, $playlist) {
        global $sql;
        if ($sql->sql_insert('source_m3u_file', [
            'user_id'  => $user_id,
            'title'    => $playlist['title'],
            'filename' => $playlist['filename']
        ])) {
            $source_m3u_file_id = $sql->last_insert_id();
            foreach ($playlist['groups'] as $key => $group) {
                if ($sql->sql_insert('category_m3u_file', [
                    'user_id'            => $user_id,
                    'source_m3u_file_id' => $source_m3u_file_id,
                    'category_id'        => $key,
                    'category_name'      => $group['name'],
                    'category_type'      => $group['category_type']
                ])) {
                    $category_m3u_file_id = $sql->last_insert_id();
                    $values = "";
                    $count  = count($group['streams']);
                    for ($i = 0; $i < $count; $i++) {
                        $stream = $group['streams'][$i];

                        $name           = $sql->clean_string($stream['name']);
                        $epg_channel_id = $sql->clean_string($stream['tvgId']);
                        $stream_icon    = $sql->clean_string($stream['tvgLogo']);
                        $url            = $sql->clean_string($stream['url']);

                        // LIVE
                        if ($group['category_type'] == 1) {
                            $values .= "('{$user_id}', '{$source_m3u_file_id}', '{$category_m3u_file_id}', '{$epg_channel_id}', '{$name}', '{$i}', '{$stream_icon}', '{$url}')";
                            if ($i < $count -1) {
                                $values .= ",";
                            }
                        }
                        // MOVIES
                        if ($group['category_type'] == 2) {
                            $extension = $sql->clean_string($stream['extension']);
                            if (isset($stream['movie'])) {
                                $name = $sql->clean_string($stream['movie']['name']);
                                $year = $sql->clean_string($stream['movie']['year']);
                            } else {
                                $year = "";
                            }
                            $values .= "('{$user_id}', '{$source_m3u_file_id}', '{$category_m3u_file_id}', '{$extension}', '{$name}', '{$year}', '{$i}', '{$stream_icon}', '{$url}')";
                            if ($i < $count -1) {
                                $values .= ",";
                            }
                        }
                        // SERIES
                        if ($group['category_type'] == 3) {
                            $extension = $sql->clean_string($stream['extension']);
                            if (isset($stream['series'])) {
                                $name    = $sql->clean_string($stream['series']['name']);
                                $episode = $sql->clean_string($stream['series']['episode']);
                                $season  = $sql->clean_string($stream['series']['season']);
                            } else {
                                $episode = "";
                                $season  = "";
                            }
                            $values .= "('{$user_id}', '{$source_m3u_file_id}', '{$category_m3u_file_id}', '{$extension}', '{$name}', '{$episode}', '{$season}', '{$i}', '{$stream_icon}', '{$url}')";
                            if ($i < $count -1) {
                                $values .= ",";
                            }
                        }
                    }

                    // LIVE
                    if ($group['category_type'] == 1) {
                        $sql->sql_query("INSERT INTO `stream_m3u_file_live` (`user_id`, `source_m3u_file_id`, `category_m3u_file_id`, `epg_channel_id`, `name`, `num`, `stream_icon`, `url`) VALUES {$values}");
                    }
                    // MOVIE
                    if ($group['category_type'] == 2) {
                        $sql->sql_query("INSERT INTO `stream_m3u_file_movies` (`user_id`, `source_m3u_file_id`, `category_m3u_file_id`, `container_extension`, `name`, `year`, `num`, `stream_icon`, `url`) VALUES {$values}");
                    }
                    // SERIES
                    if ($group['category_type'] == 3) {
                        $sql->sql_query("INSERT INTO `stream_m3u_file_series` (`user_id`, `source_m3u_file_id`, `category_m3u_file_id`, `container_extension`, `name`, `episode`, `season`, `num`, `stream_icon`, `url`) VALUES {$values}");
                    }

                }
            }
        } else {
            return $sql->sql_last_error();
        }
    }

    // Update m3u file
    function update_m3u_file ($user_id, $playlist) {
        global $sql;
        return $sql->sql_update('source_m3u_file', $playlist, ['user_id' => $user_id, 'id' => $playlist['id']]);
    }

    // Update tmdb ids - add to table
    function update_tmdb ($user_id, $table, $source_id, $language) {
        global $sql;
        $table = str_replace('-', '_', $table);
        return $sql->sql_query("INSERT INTO `sync_tmdb_{$table}` (`user_id`, `source_{$table}_id`, `language`) VALUES('{$user_id}', '{$source_id}', '{$language}') ON DUPLICATE KEY UPDATE `source_{$table}_id` = '{$source_id}', `language` = '{$language}'");
    }

    // Update source (sync playlist) - add to table
    function update_source ($user_id, $table, $source_id) {
        global $sql;
        $table = str_replace('-', '_', $table);
        return $sql->sql_query("INSERT INTO `sync_source_{$table}` (`user_id`, `source_{$table}_id`) VALUES('{$user_id}', '{$source_id}') ON DUPLICATE KEY UPDATE `source_{$table}_id` = '{$source_id}'");
    }

    // Check if there is a query waiting for this user.
    function get_waiting_tmdb ($user_id, $table) {
        global $sql;
        $table = str_replace('-', '_', $table);
        return $sql->sql_select_array_query("SELECT count(*) as active FROM `sync_tmdb_{$table}` where user_id = '{$user_id}'")[0]['active'] > 0;
    }

    // Check if there is a query waiting for this user.
    function get_waiting_source ($user_id, $table) {
        global $sql;
        $table = str_replace('-', '_', $table);
        return $sql->sql_select_array_query("SELECT count(*) as active FROM `sync_source_{$table}` where user_id = '{$user_id}'")[0]['active'] > 0;
    }

}