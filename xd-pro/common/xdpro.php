<?php

/**********************************************************************************/
/*																				  */
/*				xdpro.php 					  									  */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 05/07/2021									  		  */
/*				Version	: 1.0													  */
/*																				  */
/**********************************************************************************/

class xdpro {

	// User class constructor
	function __construct () {
		
    }
    
    // Authenticate user and get instances
    function authenticate ($username, $password) {
        global $sql;
        // Find user that matches this username and password
        $res = $sql->sql_select_array_query("SELECT * FROM `user` WHERE username = '{$username}' AND status = 2");
        if (count($res) >= 1) {
            $user    = $res[0];
            $user_id = $user['id'];
            // Compare the password
            if (hash('sha512', $password) === $user['password']) {
                $subscription = $sql->sql_select_array_query("SELECT `enabled`, UNIX_TIMESTAMP(end_date) as `end_date`, `subscription_type` FROM `subscription` WHERE user_id = '{$user_id}'")[0];
                if (!empty($subscription) && (is_null($subscription['end_date']) || $subscription['end_date'] > time()) && boolval($subscription['enabled']) === true) {
                    return $sql->sql_select_array_query("SELECT `id`, `name`, `download_folder`, `useragent`, `speed_limit`, `check_connections`, `api_key` FROM `xdpro` WHERE user_id = '{$user_id}'");
                }
                return false;
            }
        }
        return false;
    }

    // Get next download for instance
    function download ($api_key) {
        global $sql;
        // Find instance with api_key
        $res = $sql->sql_select_array_query("SELECT `id`, `download_url`, `download_host`, `download_port`, `download_username`, `download_password`, `filename`, `file_extension`, `type`, `download_folder` FROM `xdpro_downloads` WHERE `xdpro_id` = (SELECT id FROM `xdpro` WHERE BINARY api_key = '{$api_key}') AND active = 0 AND enabled = 1 AND has_error = 0 LIMIT 1");
        return count($res) === 1 ? $res[0] : false;
    }

    // Start download
    function start ($api_key, $id) {
        global $sql;
        $res = $sql->sql_select_array_query("SELECT `user_id` FROM `xdpro` WHERE BINARY api_key = '{$api_key}' LIMIT 1");
        if (count($res) === 1) {
            return $sql->sql_update('xdpro_downloads', ['active' => 1], ['id' => $id, 'user_id' => $res[0]['user_id']]);
        } else {
            return false;
        }
    }

    // Update download progress
    function progress ($id, $progress) {
        global $sql;
        return $sql->sql_update('xdpro_downloads', ['progress' => $progress], ['id' => $id]);
    }

    // Finished download
    function finished ($id) {
        global $sql;
        return $sql->sql_delete('xdpro_downloads', ['id' => $id]);
    }

    // Download error
    function error ($id, $error) {
        global $sql;
        return $sql->sql_update('xdpro_downloads', ['active' => 0, 'enabled' => 0, 'has_error' => 1, 'error' => $error], ['id' => $id]);
    }

}