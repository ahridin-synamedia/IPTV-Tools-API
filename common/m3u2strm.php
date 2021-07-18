<?php

/**********************************************************************************/
/*																				  */
/*				M3U2STRM.php 				  			    				      */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 18/07/2021									  		  */
/*				Version	: 1.0													  */
/*																				  */
/**********************************************************************************/

class m3u2strm {
    
    // Live class constructor
	function __construct () {
		
    }

    // Get M3U-2-STRM instances for user
    function get_instances ($user_id) {
        global $sql;
        return $sql->sql_select_array_query("SELECT * FROM `m3u2strm` WHERE user_id = '{$user_id}'");
    }

    // Add new M3U-2-STRM instance
    function add_instance ($user_id) {
        global $sql;
        if ($sql->sql_insert('m3u2strm', ['user_id' => $user_id, 'api_key' => md5(time() . "M3U-2-STRM")])) {
            return $sql->last_insert_id();
        } else {
            return $sql->sql_last_error();
        }
    }

    // Update M3U-2-STRM instance
    function update_instance ($user_id, $id, $instance) {
        global $sql;
        if ($sql->sql_update('m3u2strm', $instance, ['user_id' => $user_id, 'id' => $id])) {
            return true;
        } else {
            return $sql->sql_last_error();
        }
    }

    // Delete M3U-2-STRM instance
    function delete_instance ($user_id, $id) {
        global $sql;
        return $sql->sql_delete('m3u2strm', ['user_id' => $user_id, 'id' => $id]);
    }

}