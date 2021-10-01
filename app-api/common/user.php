<?php

/**********************************************************************************/
/*																				  */
/*				user.php 					  									  */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 05/08/2021									  		  */
/*				Version	: 1.0													  */
/*																				  */
/**********************************************************************************/

class user {
	private $server_key;

	// User class constructor
	function __construct ($serverKey) {
		$this->server_key = $serverKey;
    }

    // Extract the user information from the header
    function decode_token ($token) {
        return JWT::decode($token, $this->server_key, array('HS256'));
    }
    
    // Login
    function authenticate ($username, $password, $code) {
        global $sql;
        // Find user that matches this username and password
        $sha = hash('sha512', $password);
        $res = $sql->sql_select_array_query("SELECT `id`, `username`, `password`, `email`, UNIX_TIMESTAMP(created) as 'created', (SELECT `fullname` FROM `app` WHERE user_id = u.id AND code = '{$code}') as 'fullname', (SELECT `picture` FROM `app` WHERE user_id = u.id AND code = '{$code}') as 'picture', '{$code}' as 'code' FROM `user` u WHERE username = '{$username}' AND password = '{$sha}' AND status = 2 AND (SELECT count(*) FROM `app` WHERE user_id = u.id AND code = '{$code}') = 1");
        if (count($res) >= 1) {
            $user    = $res[0];
            $user_id = $user['id'];
            // Add succesfull login
            $sql->sql_insert('login', [
                'user_id'   => $user_id,
                'action'    => 1,
                'status'    => 1,
                'ipaddress' => $this->ip_address(),
                'useragent' => $_SERVER['HTTP_USER_AGENT']
            ]);
            $subscription = $sql->sql_select_array_query("SELECT * FROM `subscription` WHERE user_id = '{$user_id}'");
            return [
                'token'        => JWT::encode(['id' => $user['id'], 'code' => $user['code'], 'username' => $user['username'], 'password' => $user['password']], $this->server_key),
                'user'         => $user,
                'subscription' => $subscription ? $subscription[0] : null,
                'exp'          => date('Y-m-d H:i:s', strtotime('+12 hour'))
            ];

        } else {
            // Add failed login - username not found
            $sql->sql_insert('login', [
                'user_id'   => null,
                'action'    => 1,
                'status'    => 3,
                'ipaddress' => $this->ip_address(),
                'useragent' => $_SERVER['HTTP_USER_AGENT'],
                'error'     => [
                    'error' => 2, 
                    'username' => $username, 
                    'password' => $password
                ]
            ]);
        }
        return false;
    }

    // Logout
    function logout ($user_id) {
        global $sql;
        return $sql->sql_insert('login', [
            'user_id'   => $user_id,
            'action'    => 2,
            'status'    => 1,
            'ipaddress' => $this->ip_address(),
            'useragent' => $_SERVER['HTTP_USER_AGENT']
        ]);
    }

    // Get IP-Address
    function ip_address () {
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		    return $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		    return $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
		    return $_SERVER['REMOTE_ADDR'];
		}
	}

}