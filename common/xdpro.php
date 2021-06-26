<?php

/**********************************************************************************/
/*																				  */
/*				XDPro.php 				  			    					      */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 22/06/2021									  		  */
/*				Version	: 1.0													  */
/*																				  */
/**********************************************************************************/

class xdpro {
    
    // Live class constructor
	function __construct () {
		
    }

    // Get XD-Pro instances for user
    function get_instances ($user_id) {
        global $sql;
        return $sql->sql_select_array_query("SELECT i.*, (SELECT count(*) FROM `xdpro_downloads` WHERE user_id = '{$user_id}' AND xdpro_id = i.id) as 'downloads' FROM `xdpro` i WHERE user_id = '{$user_id}'");
    }

    // Add new XD-Pro instance
    function add_instance ($user_id) {
        global $sql;
        if ($sql->sql_insert('xdpro', ['user_id' => $user_id])) {
            return $sql->last_insert_id();
        } else {
            return $sql->sql_last_error();
        }
    }

    // Update XD-Pro instance
    function update_instance ($user_id, $id, $instance) {
        global $sql;
        if ($sql->sql_update('xdpro', $instance, ['user_id' => $user_id, 'id' => $id])) {
            return true;
        } else {
            return $sql->sql_last_error();
        }
    }

    // Delete XD-Pro instance
    function delete_instance ($user_id, $id) {
        global $sql;
        return $sql->sql_delete('xdpro', ['user_id' => $user_id, 'id' => $id]);
    }

    // Get XD-Pro downloads for user
    function get_downloads ($user_id) {
        global $sql;
        return $sql->sql_select_array_query("SELECT * FROM `xdpro_downloads` WHERE user_id = '{$user_id}'");
    }

    // Add new XD-Pro download
    function add_download ($user_id, $downloads) {
        global $sql;
        foreach ($downloads as $download) {
            $sql->sql_insert('xdpro_downloads', $download);
        }
        return $sql->sql_last_error();
    }

    // Update XD-Pro download
    function update_download ($user_id, $id, $download) {
        global $sql;
        if ($sql->sql_update('xdpro_downloads', $download, ['user_id' => $user_id, 'id' => $id])) {
            return true;
        } else {
            return $sql->sql_last_error();
        }
    }

    // Delete XD-Pro download
    function delete_download ($user_id, $id) {
        global $sql;
        return $sql->sql_delete('xdpro_downloads', ['user_id' => $user_id, 'id' => $id]);
    }

    // Pause all downloads (not active)
    function pause_downloads ($user_id) {
        global $sql;
        return $sql->sql_update('xdpro_downloads', ['enabled' => 0], ['user_id' => $user_id, 'active' => 0]);
    }

    // Resume all downloads
    function resume_downloads ($user_id) {
        global $sql;
        return $sql->sql_update('xdpro_downloads', ['enabled' => 1], ['user_id' => $user_id, 'active' => 0]);
    }

    // Delete downloads
    function delete_downloads ($user_id, $disabled = false) {
        global $sql;
        if ($disabled === true) {
            return $sql->sql_delete('xdpro_downloads', ['user_id' => $user_id, 'active' => 0, 'enabled' => 0]);
        } else {
            return $sql->sql_delete('xdpro_downloads', ['user_id' => $user_id, 'active' => 0]);
        }
    }

}