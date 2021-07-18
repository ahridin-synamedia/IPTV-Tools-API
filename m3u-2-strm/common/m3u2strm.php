<?php

/**********************************************************************************/
/*																				  */
/*				m3u2strm.php 					  								  */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 18/07/2021									  		  */
/*				Version	: 1.0													  */
/*																				  */
/**********************************************************************************/

class m3u2strm {

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
                    return $sql->sql_select_array_query("SELECT `id`, `user_id`, `name`, `file_naming_movies`, `movies_folder`, `file_naming_series`, `series_folder`, `create_nfo`, `api_key` FROM `m3u2strm` WHERE user_id = '{$user_id}'");
                }
                return false;
            }
        }
        return false;
    }

}