<?php

/**********************************************************************************/
/*																				  */
/*				Siptv.php 				  			    					      */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 06/07/2021									  		  */
/*				Version	: 1.0													  */
/*																				  */
/**********************************************************************************/

class siptv {
    
    // Live class constructor
	function __construct () {
		
    }

    // Get siptv profiles for user
    function get_profiles ($user_id) {
        global $sql;
        return $sql->sql_select_array_query("SELECT * FROM `smartiptv_profiles` WHERE user_id = '{$user_id}'");
    }

    // Add new siptv profile
    function add_profile ($user_id) {
        global $sql;
        if ($sql->sql_insert('smartiptv_profiles', ['user_id' => $user_id])) {
            return $sql->last_insert_id();
        } else {
            return $sql->sql_last_error();
        }
    }

    // Update siptv profile
    function update_profile ($user_id, $id, $profile) {
        global $sql;
        if ($sql->sql_update('smartiptv_profiles', $profile, ['user_id' => $user_id, 'id' => $id])) {
            return true;
        } else {
            return $sql->sql_last_error();
        }
    }

    // Delete siptv profile
    function delete_profile ($user_id, $id) {
        global $sql;
        return $sql->sql_delete('smartiptv_profiles', ['user_id' => $user_id, 'id' => $id]);
    }

    // Delete playlist from MAC
    function delete_playlist ($mac) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://siptv.eu/scripts/reset_list.php");
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "mac={$mac}&lang=en");
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			"cookie:origin=valid; captcha=1",
			"origin:https://siptv.eu",
			"referer:https://siptv.app/mylist/",
			"x-requested-with:XMLHttpRequest",
			"content-type:application/x-www-form-urlencoded; charset=UTF-8"
		]);
        $html = curl_exec($ch);
		curl_close($ch);
        return $html;
    }

    // Upload playlist to mac
    function upload_playlist ($user_id, $profile) {
        global $sql;
        $id  = $profile['playlist'];
        $res = $sql->sql_select_array_query("SELECT * FROM `playlist` WHERE id = '{$id}' AND user_id = '{$user_id}' LIMIT 1");
        if (count($res) === 1) {
            $username = $res[0]['api_username'];
            $password = $res[0]['api_password'];
        } else {
            return "";
        }
        $ch = curl_init();
		$params = [
            "mac"           => $profile['mac'],
            "sel_countries" => $profile['epg_country'],
            "sel_logos"     => $profile['logos'],
            "lang"          => "en",
            "url1"          => "http://tv.iptv-tools.com/get.php?username={$username}&password={$password}&type=m3u_plus",
            "epg1"          => "http://tv.iptv-tools.com/xmltv.php?username={$username}&password={$password}",
            "pin"           => $profile['pin'],
            "url_count"     => 1,
            "file_selected" => 0,
            "plist_order"   => 0
        ];
        if (boolval($profile['save_online']) === true) {
            $params['keep'] = 'on';
        }
        if (boolval($profile['detect_epg']) === true) {
            $params['detect_epg'] = 'on';
        }
        if (boolval($profile['disable_groups']) === true) {
            $params['disable_groups'] = 'on';
        }
        curl_setopt($ch, CURLOPT_URL, "https://siptv.app/scripts/up_url_only.php");
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			"cookie:origin=valid; captcha=1",
			"origin:https://siptv.eu",
			"referer:https://siptv.app/mylist/",
			"x-requested-with:XMLHttpRequest",
			"content-type:application/x-www-form-urlencoded; charset=UTF-8"
		]);
        $html = curl_exec($ch);
		curl_close($ch);
        return $html;
    }

}