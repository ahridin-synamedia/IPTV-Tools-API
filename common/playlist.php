<?php

/**********************************************************************************/
/*																				  */
/*				playlist.php 				  			    					  */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 30/05/2021									  		  */
/*				Version	: 1.0													  */
/*																				  */
/**********************************************************************************/

class playlist {
    
    // Playlist class constructor
	function __construct () {
		
    }

    // Get playlists for user
    function get ($user_id, $simple = false) {
        global $sql;
        if ($simple === true) {
            return $sql->sql_select_array_query("SELECT `id`, `name` FROM `playlist` WHERE user_id = '{$user_id}'");
        }
        return $sql->sql_select_array_query("SELECT *, (SELECT count(*) FROM `groups` WHERE user_id = '{$user_id}' AND playlist_id = p.id) as `groups`, (SELECT count(*) FROM `live` WHERE user_id = '{$user_id}' AND playlist_id = p.id) + (SELECT count(*) FROM `movie` WHERE user_id = '{$user_id}' AND playlist_id = p.id) + (SELECT count(*) FROM `episodes` WHERE user_id = '{$user_id}' AND playlist_id = p.id) as `streams` FROM `playlist` p WHERE user_id = '{$user_id}' ORDER BY id ASC");
    }

    // Add new empty playlist
    function add ($user_id) {
        global $sql;
        if ($sql->sql_insert('playlist', ['user_id' => $user_id])) {
            return $sql->last_insert_id();
        } else {
            return false;
        }
    }

    // Update playlist
    function update ($user_id, $playlist_id, $playlist) {
        global $sql;
        unset($playlist['last_updated']);
        if ($sql->sql_update('playlist', $playlist, ['user_id' => $user_id, 'id' => $playlist_id])) {
            return true;
        } else {
            return $sql->sql_last_error();
        }
    }

    // Delete playlist
    function delete ($user_id, $playlist_id) {
        global $sql;
        $sql->sql_delete('groups',          ['user_id' => $user_id, 'playlist_id' => $playlist_id]);
        $sql->sql_delete('live',            ['user_id' => $user_id, 'playlist_id' => $playlist_id]);
        $sql->sql_delete('movie',           ['user_id' => $user_id, 'playlist_id' => $playlist_id]);
        $sql->sql_delete('episode',         ['user_id' => $user_id, 'playlist_id' => $playlist_id]);
        $sql->sql_delete('series_tmdb',     ['user_id' => $user_id, 'playlist_id' => $playlist_id]);
        return $sql->sql_delete('playlist', ['user_id' => $user_id, 'id' => $playlist_id]);
    }

    // Synchronize playlist (Streams)
    function synchronize ($user_id, $playlist_id) {
        global $sql;
        return $sql->sql_insert('sync_playlist', ['user_id' => $user_id, 'playlist_id' => $playlist_id]);
    }

    // Synchronize playlist tmdb info
    function synchronize_tmdb ($user_id, $playlist_id) {
        global $sql;
        return $sql->sql_insert('sync_tmdb', ['user_id' => $user_id, 'playlist_id' => $playlist_id]);
    }

    // Is playlist synchronization active
    function synchronize_active ($user_id) {
        global $sql;
        return $sql->sql_select_array_query("SELECT count(*) as `active` FROM `sync_playlist` WHERE user_id = '{$user_id}'")[0]['active'] > 0;
    }

    // Is playlist tmdb synchronization active
    function synchronize_tmdb_active ($user_id) {
        global $sql;
        return $sql->sql_select_array_query("SELECT count(*) as `active` FROM `sync_tmdb` WHERE user_id = '{$user_id}'")[0]['active'] > 0;
    }

}