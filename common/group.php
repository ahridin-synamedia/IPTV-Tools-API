<?php

/**********************************************************************************/
/*																				  */
/*				group.php 				  			    					      */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 30/05/2021									  		  */
/*				Version	: 1.0													  */
/*																				  */
/**********************************************************************************/

class group {
    
    // Group class constructor
	function __construct () {
		
    }

    // Get groups for user
    function get ($user_id, $from, $amount, $simple = false) {
        global $sql;
        if ($simple === true) {
            return $sql->sql_select_array_query("SELECT `id`, `group_name`, `source_group_type`, `group_type` FROM `groups` WHERE user_id = '{$user_id}'");
        }
        return $sql->sql_select_array_query("SELECT *, (SELECT name FROM `playlist` WHERE `id` = g.playlist_id) as playlist, CASE WHEN g.group_type = 1 THEN (SELECT count(*) FROM live WHERE group_id = g.id) WHEN g.group_type = 2 THEN (SELECT count(*) FROM movie WHERE group_id = g.id) WHEN g.group_type = 3 THEN (SELECT count(*) FROM episodes WHERE group_id = g.id) END as streams FROM `groups` g WHERE user_id = '{$user_id}' LIMIT {$from}, {$amount}");
    }

    function get_catchup ($user_id, $playlist_id) {
        global $sql;
        return $sql->sql_select_array_query("SELECT * FROM `groups` g WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND (SELECT count(*) FROM `live` WHERE source_tv_archive = 1 AND group_id = g.id) > 0");
    }

    // Add new empty group
    function add ($user_id) {
        global $sql;
        $source_category_id = $this->new_custom_source_category_id($user_id);
        if ($sql->sql_insert('groups', ['user_id' => $user_id, 'source_category_id' => $source_category_id, 'group_is_custom' => true])) {
            return $sql->last_insert_id();
        } else {
            return $sql->sql_last_error();
        }
    }

    // Update group
    function update ($user_id, $group_id, $group) {
        global $sql;
        if ($sql->sql_update('groups', $group, ['user_id' => $user_id, 'id' => $group_id])) {
            return true;
        } else {
            return $sql->sql_last_error();
        }
    }

    // Delete group
    function delete ($user_id, $group_id) {
        global $sql;
        return $sql->sql_delete('groups', ['user_id' => $user_id, 'id' => $group_id]);
    }

    // Get count of groups
    function total ($user_id) {
        global $sql;
        $result = $sql->sql_select_array_query("SELECT count(*) as `total` FROM `groups` WHERE user_id = '{$user_id}'");
        return count($result) ? $result[0]['total'] : 0;
    }

    // Get highest source_category_id for custom group
    function new_custom_source_category_id ($user_id) {
        global $sql;
        $result = $sql->sql_select_array_query("SELECT source_category_id FROM `groups` WHERE user_id = '{$user_id}' AND group_is_custom = true ORDER BY source_category_id DESC");
        return count($result) > 0 ? intval($result[0]['source_category_id']) +1 : 1;
    }

}