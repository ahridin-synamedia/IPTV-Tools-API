<?php

/**********************************************************************************/
/*																				  */
/*				editor.php 				  			    					      */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 30/05/2021									  		  */
/*				Version	: 1.0													  */
/*																				  */
/**********************************************************************************/

class editor {
    
    // Editor class constructor
	function __construct () {
		
    }

    // Get playlist details
    function playlist ($user_id, $playlist_id) {
        global $sql;
        $result = $sql->sql_select_array_query("SELECT * FROM `playlist` WHERE user_id = '{$user_id}' AND id = '{$playlist_id}'");
        if (count($result) === 1) {
            return $result[0];
        } else {
            return false;
        }
    }
    
    // Get groups for playlist
    function groups ($user_id, $playlist_id) {
        global $sql;
        return $sql->sql_select_array_query("SELECT id, group_name, group_order, group_type, group_parent_code, group_is_hidden, CASE WHEN g.group_type = 1 THEN (SELECT count(*) FROM live WHERE group_id = g.id) WHEN g.group_type = 2 THEN (SELECT count(*) FROM movie WHERE group_id = g.id) WHEN g.group_type = 3 THEN (SELECT count(*) FROM episodes WHERE group_id = g.id) END as streams FROM `groups` g WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' ORDER BY group_order");
    }

    // Import groups with streams
    function import ($user_id, $playlist_id, $import) {
        global $sql;
        $groups  = 0;
        $streams = 0;
        foreach ($import as $group) {
            $source_category_id = $this->new_custom_source_category_id($user_id);
            if ($sql->sql_insert('groups', [
                'user_id'            => $user_id, 
                'playlist_id'        => $playlist_id,
                'source_category_id' => $source_category_id, 
                'source_group_type'  => $group['type'],
                'source_group_name'  => $group['name'],
                'group_type'         => $group['type'],
                'group_name'         => $group['name'],
                'group_is_custom'    => true
            ])) {
                $groups++;
                $group_id = $sql->last_insert_id();
                foreach ($group['streams'] as $stream) {
                    $streams++;
                    switch (intval($stream['streamType'])) {
                        // Live
                        case 1:
                            $sql->sql_insert('live', [
                                'user_id'           => $user_id,
                                'playlist_id'       => $playlist_id,
                                'group_id'          => $group_id,
                                'source_stream_id'  => $this->new_Live_custom_source_stream_id($user_id),
                                'source_stream_url' => $stream['url'],
                                'stream_is_custom'  => true,
                                'stream_tvg_name'   => $stream['name'],
                                'stream_tvg_id'     => $stream['tvgId'],
                                'stream_tvg_logo'   => $stream['tvgLogo'],
                                'stream_radio'      => 0,
                                'stream_order'      => 0
                            ]);
                            break;
                        
                        // Movie
                        case 2:
                            $sql->sql_insert('movie', [
                                'user_id'           => $user_id,
                                'playlist_id'       => $playlist_id,
                                'group_id'          => $group_id,
                                'source_stream_id'  => $this->new_movie_custom_source_stream_id($user_id),
                                'source_stream_url' => $stream['url'],
                                'stream_is_custom'  => true,
                                'stream_tvg_name'   => $stream['name'],
                                'stream_tvg_id'     => $stream['tvgId'],
                                'stream_tvg_logo'   => $stream['tvgLogo'],
                                'stream_radio'      => 0,
                                'stream_order'      => 0,
                                'movie_name'        => isset($stream['movie']) && isset($stream['movie']['name']) ? $stream['movie']['name'] : "",
                                'movie_year'        => isset($stream['movie']) && isset($stream['movie']['year']) ? $stream['movie']['year'] : "",
                                'tmdb_id'           => ""
                            ]);
                            break;
            
                        // Series
                        case 3:
                            $sql->sql_insert('episodes', [
                                'user_id'           => $user_id,
                                'playlist_id'       => $playlist_id,
                                'group_id'          => $group_id,
                                'source_stream_id'  => $this->new_serie_custom_source_stream_id($user_id),
                                'source_stream_url' => $stream['url'],
                                'stream_is_custom'  => true,
                                'stream_tvg_name'   => $stream['name'],
                                'stream_tvg_id'     => $stream['tvgId'],
                                'stream_tvg_logo'   => $stream['tvgLogo'],
                                'stream_radio'      => 0,
                                'stream_order'      => 0,
                                'serie_name'        => isset($stream['series']) && isset($stream['series']['name']) ? $stream['series']['name'] : "",
                                'serie_season'      => isset($stream['series']) && isset($stream['series']['episode']) ? intval($stream['series']['episode']) : 0,
                                'serie_episode'     => isset($stream['series']) && isset($stream['series']['season']) ? intval($stream['series']['season']) : 0,
                            ]);
                            break;
                    }
                }
            }
        }
        return [
            'groups'  => $groups,
            'streams' => $streams,
            'error'   => $sql->sql_last_error()
        ];
    }

    // Add group in playlist editor
    function add_group ($user_id, $playlist_id) {
        global $sql;
        $source_category_id = $this->new_custom_source_category_id($user_id);
        if ($sql->sql_insert('groups', ['user_id' => $user_id, 'source_category_id' => $source_category_id, 'playlist_id' => $playlist_id, 'group_is_custom' => true])) {
            $id  = $sql->last_insert_id();
            $res = $sql->sql_select_array_query("SELECT id, group_name, group_order, group_type, group_parent_code, group_is_hidden, false as group_is_removed, g.group_parent_code as source_group_parent_code, g.group_is_hidden as source_group_is_hidden, g.group_name as source_group_name, 0 as streams FROM `groups` g WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND id = '{$id}'");
            return count($res) === 1 ? $res[0] : false;
        } else {
            return $sql->sql_last_error();
        }
    }

    // Add stream in playlist editor
    function add_stream ($user_id, $playlist_id, $group_id, $type) {
        global $sql;
        switch (intval($type)) {
            // Live
            case 1:
                if ($sql->sql_insert('live', [
                    'user_id'           => $user_id,
                    'playlist_id'       => $playlist_id,
                    'group_id'          => $group_id,
                    'source_stream_id'  => $this->new_Live_custom_source_stream_id($user_id),
                    'source_stream_url' => "",
                    'stream_is_custom'  => true,
                    'stream_tvg_name'   => "",
                    'stream_tvg_id'     => "",
                    'stream_tvg_logo'   => "",
                    'stream_radio'      => 0,
                    'stream_order'      => 0
                 ])) {
                    $id  = $sql->last_insert_id();
                    $res = $sql->sql_select_array_query("SELECT `id`, `source_stream_url`, `stream_tvg_name`, `stream_tvg_id`, `stream_tvg_logo`, `stream_tvg_chno`, `stream_tvg_shift`, `stream_parent_code`, `stream_audio_track`, `stream_aspect_ratio`, `stream_radio`, `stream_order`, `stream_is_hidden`, `stream_is_custom`, `sync_is_new`, `sync_is_removed` FROM `live` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND group_id = '{$group_id}' AND id = '{$id}'");
                    return count($res) === 1 ? $res[0] : false;
                } else {
                    return $sql->sql_last_error();
                }
                break;
            
            // Movie
            case 2:
                if ($sql->sql_insert('movie', [
                    'user_id'           => $user_id,
                    'playlist_id'       => $playlist_id,
                    'group_id'          => $group_id,
                    'source_stream_id'  => $this->new_movie_custom_source_stream_id($user_id),
                    'source_stream_url' => "",
                    'stream_is_custom'  => true,
                    'stream_tvg_name'   => "",
                    'stream_tvg_id'     => "",
                    'stream_tvg_logo'   => "",
                    'stream_radio'      => 0,
                    'stream_order'      => 0,
                    'movie_name'        => "",
                    'movie_year'        => "",
                    'tmdb_id'           => ""
                 ])) {
                    $id  = $sql->last_insert_id();
                    $res = $sql->sql_select_array_query("SELECT `id`, `source_stream_url`, `stream_tvg_name`, `stream_tvg_id`, `stream_tvg_logo`, `stream_tvg_chno`, `stream_tvg_shift`, `stream_parent_code`, `stream_audio_track`, `stream_aspect_ratio`, `stream_order`, `stream_is_hidden`, `sync_is_new`, `sync_is_removed`, `stream_is_custom`, `movie_name`, `movie_year`, `tmdb_id` FROM `movie` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND group_id = '{$group_id}' AND id = '{$id}'");
                    return count($res) === 1 ? $res[0] : false;
                } else {
                    return $sql->sql_last_error();
                }
                break;

            // Series
            case 3:
                if ($sql->sql_insert('episodes', [
                    'user_id'           => $user_id,
                    'playlist_id'       => $playlist_id,
                    'group_id'          => $group_id,
                    'source_stream_id'  => $this->new_serie_custom_source_stream_id($user_id),
                    'source_stream_url' => "",
                    'stream_is_custom'  => true,
                    'stream_tvg_name'   => "",
                    'stream_tvg_id'     => "",
                    'stream_tvg_logo'   => "",
                    'stream_radio'      => 0,
                    'stream_order'      => 0,
                    'serie_name'        => "",
                    'serie_season'      => 0,
                    'serie_episode'     => 0
                 ])) {
                    $id  = $sql->last_insert_id();
                    $res = $sql->sql_select_array_query("SELECT `id`, `source_stream_url`, `stream_tvg_name`, `stream_tvg_id`, `stream_tvg_logo`, `stream_tvg_chno`, `stream_tvg_shift`, `stream_parent_code`, `stream_audio_track`, `stream_aspect_ratio`, `stream_order`, `stream_is_hidden`, `sync_is_new`, `sync_is_removed`, `stream_is_custom`, `serie_name`, `serie_season`, `serie_episode` FROM `episodes` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND group_id = '{$group_id}' AND id = '{$id}'");
                    return count($res) === 1 ? $res[0] : false;
                } else {
                    return $sql->sql_last_error();
                }
                break;
        }
        /*
        $source_category_id = $this->new_custom_source_category_id($user_id);
        if ($sql->sql_insert('groups', ['user_id' => $user_id, 'source_category_id' => $source_category_id, 'playlist_id' => $playlist_id, 'group_is_custom' => true])) {
            $id  = $sql->last_insert_id();
            $res = $sql->sql_select_array_query("SELECT id, group_name, group_order, group_type, group_parent_code, group_is_hidden, false as group_is_removed, g.group_parent_code as source_group_parent_code, g.group_is_hidden as source_group_is_hidden, g.group_name as source_group_name, 0 as streams FROM `groups` g WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND id = '{$id}'");
            return count($res) === 1 ? $res[0] : false;
        } else {
            return $sql->sql_last_error();
        }*/
    }

    // Move streams from group to another group
    function move_streams_to_group ($user_id, $playlist_id, $streams, $group_id, $type) {
        global $sql;
        switch (intval($type)) {
            case 1: return $sql->sql_query("UPDATE `live` SET `group_id` = '{$group_id}' WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND id IN ({$streams})");
            case 2: return $sql->sql_query("UPDATE `movie` SET `group_id` = '{$group_id}' WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND id IN ({$streams})");
            case 3: return $sql->sql_query("UPDATE `episodes` SET `group_id` = '{$group_id}' WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND id IN ({$streams})");
        }
    }

    // Update groups
    function update_groups ($user_id, $playlist_id, $groups) {
        global $sql;
        foreach ($groups as $group) {
            $sql->sql_update('groups', $group, ['user_id' => $user_id, 'playlist_id' => $playlist_id, 'id' => $group['id']]);
        }
        return $sql->sql_last_error();
    }

    // Delete groups
    function delete_groups ($user_id, $playlist_id, $groups) {
        global $sql;
        $sql->sql_query("DELETE FROM `live` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND group_id IN ({$groups})");
        $sql->sql_query("DELETE FROM `movie` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND group_id IN ({$groups})");
        $sql->sql_query("DELETE FROM `episodes` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND group_id IN ({$groups})");
        $sql->sql_query("DELETE FROM `groups` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND id IN ({$groups})");
        return $sql->sql_last_error();
    }

    // Update streams
    function update_streams ($user_id, $playlist_id, $streams, $type) {
        global $sql;
        switch (intval($type)) {
            // Live
            case 1:
                foreach ($streams as $stream) {
                    $sql->sql_update('live', $stream, ['user_id' => $user_id,  'playlist_id' => $playlist_id, 'id' => $stream['id']]);
                }
                break;
            // Movies
            case 2:
                foreach ($streams as $stream) {
                    $sql->sql_update('movie', $stream, ['user_id' => $user_id, 'playlist_id' => $playlist_id, 'id' => $stream['id']]);
                }
                break;
            // Series 
            case 3:
                foreach ($streams as $stream) {
                    $sql->sql_update('episodes', $stream, ['user_id' => $user_id, 'playlist_id' => $playlist_id, 'id' => $stream['id']]);
                }
                break; 
        }
        return $sql->sql_last_error();
    }

    // Delete streams
    function delete_streams ($user_id, $playlist_id, $streams, $type) {
        global $sql;
        switch (intval($type)) {
            case 1: return $sql->sql_query("DELETE FROM `live` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND id IN ({$streams})");
            case 2: return $sql->sql_query("DELETE FROM `movie` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND id IN ({$streams})");
            case 3: return $sql->sql_query("DELETE FROM `episodes` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND id IN ({$streams})");
        }
    }

    // Get streams for group
    function streams ($user_id, $playlist_id, $group_id, $group_type) {
        global $sql;
        switch (intval($group_type)) {
            case 1: return $sql->sql_select_array_query("SELECT `id`, `source_stream_url`, `stream_tvg_name`, `stream_tvg_id`, `stream_tvg_logo`, `stream_tvg_chno`, `stream_tvg_shift`, `stream_parent_code`, `stream_audio_track`, `stream_aspect_ratio`, `stream_radio`, `stream_order`, `stream_is_hidden`, `stream_is_custom`, `sync_is_new`, `sync_is_removed` FROM `live` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND group_id = '{$group_id}' ORDER BY stream_order");
            case 2: return $sql->sql_select_array_query("SELECT `id`, `source_stream_url`, `stream_tvg_name`, `stream_tvg_id`, `stream_tvg_logo`, `stream_tvg_chno`, `stream_tvg_shift`, `stream_parent_code`, `stream_audio_track`, `stream_aspect_ratio`, `stream_order`, `stream_is_hidden`, `sync_is_new`, `sync_is_removed`, `stream_is_custom`, `movie_name`, `movie_year`, `tmdb_id` FROM `movie` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND group_id = '{$group_id}' ORDER BY stream_order");
            case 3: return $sql->sql_select_array_query("SELECT `id`, `source_stream_url`, `stream_tvg_name`, `stream_tvg_id`, `stream_tvg_logo`, `stream_tvg_chno`, `stream_tvg_shift`, `stream_parent_code`, `stream_audio_track`, `stream_aspect_ratio`, `stream_order`, `stream_is_hidden`, `sync_is_new`, `sync_is_removed`, `stream_is_custom`, `serie_name`, `serie_season`, `serie_episode`, `tmdb_id` FROM `episodes` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND group_id = '{$group_id}' ORDER BY stream_order");
        }
    }

    // Add multiple streams to group
    function add_streams ($user_id, $playlist_id, $group_id, $streams, $type) {
        global $sql;
        switch (intval($type)) {
            // Live
            case 1:
                foreach ($streams as $stream) {
                    $sql->sql_insert('live', [
                       'user_id'           => $user_id,
                       'playlist_id'       => $playlist_id,
                       'group_id'          => $group_id,
                       'source_stream_id'  => $this->new_Live_custom_source_stream_id($user_id),
                       'source_stream_url' => $stream['source_stream_url'],
                       'stream_is_custom'  => true,
                       'stream_tvg_name'   => $stream['stream_tvg_name'],
                       'stream_tvg_id'     => $stream['stream_tvg_id'],
                       'stream_tvg_logo'   => $stream['stream_tvg_logo'],
                       'stream_radio'      => isset($stream['stream_radio']) ? $stream['stream_radio'] : 0,
                       'stream_order'      => $stream['stream_order']
                    ]);
                }
                break;
            // Movies
            case 2:
                foreach ($streams as $stream) {
                    $sql->sql_insert('movie', [
                        'user_id'           => $user_id,
                        'playlist_id'       => $playlist_id,
                        'group_id'          => $group_id,
                        'source_stream_id'  => $this->new_movie_custom_source_stream_id($user_id),
                        'source_stream_url' => $stream['source_stream_url'],
                        'stream_is_custom'  => true,
                        'stream_tvg_name'   => $stream['stream_tvg_name'],
                        'stream_tvg_id'     => $stream['stream_tvg_id'],
                        'stream_tvg_logo'   => $stream['stream_tvg_logo'],
                        'stream_radio'      => isset($stream['stream_radio']) ? $stream['stream_radio'] : 0,
                        'stream_order'      => $stream['stream_order'],
                        'movie_name'        => $stream['movie_name'],
                        'movie_year'        => $stream['movie_year'],
                        'tmdb_id'           => $stream['tmdb_id']
                     ]);
                }
                break;
            // Series 
            case 3:
                foreach ($streams as $stream) {
                    $sql->sql_insert('episodes', [
                        'user_id'           => $user_id,
                        'playlist_id'       => $playlist_id,
                        'group_id'          => $group_id,
                        'source_stream_id'  => $this->new_serie_custom_source_stream_id($user_id),
                        'source_stream_url' => $stream['source_stream_url'],
                        'stream_is_custom'  => true,
                        'stream_tvg_name'   => $stream['stream_tvg_name'],
                        'stream_tvg_id'     => $stream['stream_tvg_id'],
                        'stream_tvg_logo'   => $stream['stream_tvg_logo'],
                        'stream_radio'      => isset($stream['stream_radio']) ? $stream['stream_radio'] : 0,
                        'stream_order'      => $stream['stream_order'],
                        'serie_name'        => $stream['serie_name'],
                        'serie_season'      => $stream['serie_season'],
                        'serie_episode'     => $stream['serie_episode']
                     ]);
                }
                break; 
        }
        return $sql->sql_last_error();
    }

    // Get highest source_category_id for custom group
    function new_custom_source_category_id ($user_id) {
        global $sql;
        $result = $sql->sql_select_array_query("SELECT source_category_id FROM `groups` WHERE user_id = '{$user_id}' AND group_is_custom = true ORDER BY source_category_id DESC");
        return count($result) > 0 ? intval($result[0]['source_category_id']) +1 : 1;
    }

    // Get highest source_stream_id for live
    function new_Live_custom_source_stream_id ($user_id) {
        global $sql;
        $result = $sql->sql_select_array_query("SELECT source_stream_id FROM `live` WHERE user_id = '{$user_id}' AND stream_is_custom = true ORDER BY source_stream_id DESC");
        return count($result) > 0 ? intval($result[0]['source_stream_id']) +1 : 1;
    }

    // Get highest source_stream_id for movie
    function new_movie_custom_source_stream_id ($user_id) {
        global $sql;
        $result = $sql->sql_select_array_query("SELECT source_stream_id FROM `movie` WHERE user_id = '{$user_id}' AND stream_is_custom = true ORDER BY source_stream_id DESC");
        return count($result) > 0 ? intval($result[0]['source_stream_id']) +1 : 1;
    }

    // Get highest source_stream_id for serie
    function new_serie_custom_source_stream_id ($user_id) {
        global $sql;
        $result = $sql->sql_select_array_query("SELECT source_stream_id FROM `episodes` WHERE user_id = '{$user_id}' AND stream_is_custom = true ORDER BY source_stream_id DESC");
        return count($result) > 0 ? intval($result[0]['source_stream_id']) +1 : 1;
    }

}

