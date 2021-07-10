<?php

/**********************************************************************************/
/*																				  */
/*				live.php 				  			    					      */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 30/05/2021									  		  */
/*				Version	: 1.0													  */
/*																				  */
/**********************************************************************************/

class live {
    
    // Live class constructor
	function __construct () {
		
    }

    // Get live streams for user
    function get ($user_id, $from, $amount, $simple = false) {
        global $sql;
        if ($simple === true) {
            return $sql->sql_select_array_query("SELECT `id`, `stream_tvg_name`, `source_tvg_name` FROM `live` WHERE user_id = '{$user_id}'");
        }
        return $sql->sql_select_array_query("SELECT *, (SELECT name FROM `playlist` WHERE `id` = s.playlist_id) AS `playlist`, (SELECT group_name FROM `groups` WHERE `id` = s.group_id) AS `group` FROM `live` s WHERE user_id = '{$user_id}' LIMIT {$from}, {$amount}");
    }

    function get_catchup ($user_id, $playlist_id, $group_id, $date) {
        global $sql;
        $stations = [];
        $playlist = $sql->sql_select_array_query("SELECT * FROM `playlist` WHERE user_id = '{$user_id}' AND id = '{$playlist_id}' LIMIT 1");
        if (count($playlist) === 1) {
            $timeshift = intval($playlist[0]['epg_offset']) * 3600;
            $stations  = $sql->sql_select_array_query("SELECT id, source_tv_archive_duration, stream_tvg_name, stream_tvg_id, stream_tvg_logo, source_stream_id FROM `live` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND group_id = '{$group_id}' AND source_tv_archive = 1 ORDER BY stream_order ASC");
            foreach ($stations as &$station) {
                $station['programmes'] = $sql->sql_select_array_query("SELECT DATE_ADD(p.start, INTERVAL p.offset + {$timeshift} second) as 'start', DATE_ADD(p.stop, INTERVAL p.offset + {$timeshift} second) as 'stop', p.title, p.description, p.subtitle, p.season, p.episode, p.year FROM `xmltv_programmes` p WHERE tvg_id = '{$station['stream_tvg_id']}' AND DATE_ADD(p.start, INTERVAL p.offset + {$timeshift} second) LIKE '{$date}%' AND DATE_ADD(p.stop, INTERVAL p.offset + {$timeshift} second) < NOW() ORDER BY start ASC");
            }
        }
        return $stations;
    }

    // Add new empty live stream
    function add ($user_id) {
        global $sql;
        $source_stream_id = $this->new_custom_source_stream_id($user_id);
        if ($sql->sql_insert('live', ['user_id' => $user_id, 'source_stream_id' => $source_stream_id, 'stream_is_custom' => true])) {
            return $sql->last_insert_id();
        } else {
            return $sql->sql_last_error();
        }
    }

    // Update live stream
    function update ($user_id, $stream_id, $stream) {
        global $sql;
        if ($sql->sql_update('live', $stream, ['user_id' => $user_id, 'id' => $stream_id])) {
            return true;
        } else {
            return $sql->sql_last_error();
        }
    }

    // Delete live stream
    function delete ($user_id, $stream_id) {
        global $sql;
        return $sql->sql_delete('live', ['user_id' => $user_id, 'id' => $stream_id]);
    }

    // Get count of streams
    function total ($user_id) {
        global $sql;
        $result = $sql->sql_select_array_query("SELECT count(*) as `total` FROM `live` WHERE user_id = '{$user_id}'");
        return count($result) ? $result[0]['total'] : 0;
    }

    // Get highest source_stream_id for custom group
    function new_custom_source_stream_id ($user_id) {
        global $sql;
        $result = $sql->sql_select_array_query("SELECT source_stream_id FROM `live` WHERE user_id = '{$user_id}' AND stream_is_custom = true ORDER BY source_stream_id DESC");
        return count($result) > 0 ? intval($result[0]['source_stream_id']) +1 : 1;
    }

}