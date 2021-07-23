<?php

/**********************************************************************************/
/*																				  */
/*				kodi.php 				  			    					      */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 22/07/2021									  		  */
/*				Version	: 1.0													  */
/*																				  */
/**********************************************************************************/

class kodi {
    
    // Live class constructor
	function __construct () {
		
    }

    // Get Kodi Addon instances for user
    function get_instances ($user_id) {
        global $sql;
        return $sql->sql_select_array_query("SELECT * FROM `kodi` WHERE user_id = '{$user_id}'");
    }

    // Get groups of series and movies for user
    function get_groups ($user_id) {
        global $sql;
        return [
            'movies' => $sql->sql_select_array_query("SELECT * FROM `groups` where group_type = 2 and user_id = '{$user_id}'"),
            'series' => $sql->sql_select_array_query("SELECT * FROM `groups` where group_type = 3 and user_id = '{$user_id}'")
        ];
    }

    // Add new Kodi Addon instance
    function add_instance ($user_id) {
        global $sql;
        $code = $this->random_code();
        while ($this->check_code($user_id, $code)) {
            $code = $this->random_code();
        }
        if ($sql->sql_insert('kodi', ['user_id' => $user_id, 'api_key' => md5(time() . "KODI-ADDON"), 'code' => $code])) {
            return [
                'id'   => $sql->last_insert_id(),
                'code' => $code,
            ];
        } else {
            return $sql->sql_last_error();
        }
    }

    // Update Kodi Addon instance
    function update_instance ($user_id, $id, $instance) {
        global $sql;
        if ($sql->sql_update('kodi', $instance, ['user_id' => $user_id, 'id' => $id])) {
            return true;
        } else {
            return $sql->sql_last_error();
        }
    }

    // Delete Kodi Addon instance
    function delete_instance ($user_id, $id) {
        global $sql;
        return $sql->sql_delete('kodi', ['user_id' => $user_id, 'id' => $id]);
    }

    // Check if this code is already used by this user
    function check_code ($user_id, $code) {
        global $sql;
        return $sql->sql_select_array_query("SELECT count(*) as `used` FROM `kodi` WHERE user_id = '{$user_id}' AND code = '{$code}'")[0]['used'] > 0;
    }

    // Random 4 digit code
    function random_code ($digits = 4) {
        return rand(pow(10, $digits-1), pow(10, $digits)-1);
    }

}