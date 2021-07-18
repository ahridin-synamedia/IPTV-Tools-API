<?php

/**********************************************************************************/
/*																				  */
/*				user.php 					  									  */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 12/04/2021									  		  */
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
    function authenticate ($username, $password) {
        global $sql;
        // Find user that matches this username and password
        $res = $sql->sql_select_array_query("SELECT * FROM `user` WHERE username = '{$username}' AND status = 2");
        if (count($res) >= 1) {
            $user    = $res[0];
            $user_id = $user['id'];
            // Compare the password
            if (hash('sha512', $password) === $user['password']) {
                // Add succesfull login
                $sql->sql_insert('login', [
                    'user_id'   => $user_id,
                    'action'    => 1,
                    'status'    => 1,
                    'ipaddress' => $this->ip_address(),
                    'useragent' => $_SERVER['HTTP_USER_AGENT']
                ]);
                $profile      = $sql->sql_select_array_query("SELECT * FROM `profile` WHERE user_id = '{$user_id}'");
                $subscription = $sql->sql_select_array_query("SELECT * FROM `subscription` WHERE user_id = '{$user_id}'");
                return [
                    'token'        => JWT::encode($user, $this->server_key),
                    'user'         => $user,
                    'profile'      => $profile ? $profile[0] : null,
                    'subscription' => $subscription ? $subscription[0] : null,
                    'exp'          => date('Y-m-d H:i:s',strtotime('+12 hour'))
                ];
            } else {
                // Add failed login - wrong password
                $sql->sql_insert('login', [
                    'user_id'   => $user_id,
                    'action'    => 1,
                    'status'    => 2,
                    'ipaddress' => $this->ip_address(),
                    'useragent' => $_SERVER['HTTP_USER_AGENT'],
                    'error'     => [
                        'error' => 1, 
                        'username' => $username, 
                        'password' => $password
                    ]
                ]);
            }
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

    // Reset
    function reset ($username, $email) {
        global $sql;
        $user = $sql->sql_select_array_query("SELECT id FROM `user` WHERE BINARY username = '{$username}' AND email = '{$email}' LIMIT 1");
        if (count($user) === 1) {
            $data     = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcefghijklmnopqrstuvwxyz*+-';
            $user_id  = $user[0]['id'];
            $password = substr(str_shuffle($data), 0, 8);
            if ($sql->sql_update('user', [
                'password' => hash('sha512', $password),
            ], [
                'id' => $user_id
            ])) {
                $this->send_email(
                    EMAIL['email'], 
                    $email, 
                    'Your new temporary password.', 
                    $this->reset_password_body($password)
                );
                return true;
            }
        }
        return false;
    }

    // Register
    function register ($username, $password, $email) {
        global $sql;
        if ($this->available($username)) {
            // Create new user
            if ($sql->sql_insert('user', [
                'username' => $username,
                'password' => hash('sha512', $password),
                'email'    => $email,
                'status'   => 1
            ])) {
                $id = $sql->last_insert_id();
                $code = md5($username . time() . $email);
                if ($sql->sql_insert('confirm', [
                    'user_id' => $id,
                    'code'    => $code
                ])) {
                    $this->send_email(
                        EMAIL['email'], 
                        $email, 
                        'Please verify your E-mail address to start using IPTV-Tools.', 
                        $this->confirm_email_body($email, $code)
                    );
                    return true;
                }
            }
        }
        return false;
    }

    // Confirm email
    function confirm ($code) {
        global $sql;
        $res = $sql->sql_select_array_query("SELECT user_id FROM `confirm` WHERE BINARY code = '{$code}' LIMIT 1");
        if (count($res) === 1) {
            $user_id = $res[0]['user_id'];
            $sql->sql_delete('confirm', ['user_id' => $user_id]);
            $sql->sql_insert('subscription', [
                'user_id'           => $user_id,
                'enabled'           => 1,
                'end_date'          => date('Y-m-d H:i:s', strtotime("+7 day")),
                'max_playlist'      => 2,
                'playlist_type'     => 0,
                'subscription_type' => 1,
                'custom_plan'       => 0,
                'note'              => ''
            ]);
            return $sql->sql_update('user', [
                'status' => 2,
            ], [
                'id' => $user_id
            ]);
        }
        return false;
    }

    // Register / Update - Profile
    function register_profile ($profile, $update = false, $id = null) {
        global $sql;
        // Update or add profile for user
        if ($update === true) {
            return $sql->sql_update('profile', $profile, $id);
        } else {
            $p = $this->profile($profile['user_id']);
            if ($p) {
                $user_id = $profile['user_id'];
                unset($profile['id']);
                unset($profile['user_id']);
                return $sql->sql_update('profile', $profile, ['user_id' => $user_id]);
            } else {
                return $sql->sql_insert('profile', $profile);
            }
        }
    }
    
    // Get User profile information
    function profile ($user_id) {
        global $sql;
        $res = $sql->sql_select_array_query("SELECT * FROM `profile` WHERE user_id = '{$user_id}'");
        return count($res) > 0 ? $res[0] : null;
    }

    // Available
    function available ($username) {
        global $sql;
        // Check if the given username is still available
        return intval($sql->sql_select_array_query("SELECT count(*) as available FROM `user` WHERE username = '{$username}'")[0]['available']) === 0;
    }

    // Get invoices for active user
    function invoices ($user_id) {
        global $sql;
        return $sql->sql_select_array_query("SELECT * FROM `invoice` WHERE user_id = '{$user_id}' ORDER BY invoice_date DESC");
    }

    // Get subscriptions for active user
    function subscriptions ($user_id) {
        global $sql;
        return $sql->sql_select_array_query("SELECT * FROM `subscription` WHERE user_id = '{$user_id}' ORDER BY id DESC");
    }

    // Get tickets for active user
    function tickets ($user_id) {
        global $sql;
        return $sql->sql_select_array_query("SELECT * FROM `tickets` WHERE user_id = '{$user_id}' ORDER BY id DESC");
    }

    // Delete ticket for active user
    function delete_ticket ($user_id, $id) {
        global $sql;
        if (is_array($id)) {
            foreach($id as $_id) {
                $sql->sql_delete('tickets', ['user_id' => $user_id, 'id' => $_id]);
            }
            return true;
        } else {
            return $sql->sql_delete('tickets', ['user_id' => $user_id, 'id' => $id]);
        }
    }

    // Archive ticket
    function archive_ticket ($user_id, $id) {
        global $sql;
        return $sql->sql_update('tickets', ['ticket_status' => 3], ['user_id' => $user_id, 'id' => $id]);
    }

    // Add or update ticket
    function ticket ($ticket) {
        global $sql;
        if (isset($ticket['id'])) {
            $id = $ticket['id'];
            unset($ticket['id']);
            $sql->sql_update('tickets', $ticket, ['id' => $id]);
        } else {
            $sql->sql_insert('tickets', $ticket);
        }
        return $sql->sql_last_error();
    }

    // Create invoice for user
    function create_invoice ($invoice) {
        global $sql;
        return $sql->sql_insert('invoice', $invoice);
    }

    // Create payment
    function create_payment ($payment) {
        global $sql;
        return $sql->sql_insert('payment', $payment);
    }

    // Register the subscription for user - or update current subscription
    function register_subscription ($user_id, $subscription) {
        global $sql;
        if (empty($subscription['note']) || $subscription['note']['status'] !== 'COMPLETED') {
            return false;
        }
        $result  = false;
        $current = $sql->sql_select_array_query("SELECT * FROM `subscription` WHERE user_id = '{$user_id}' ORDER BY id DESC");
        if (!empty($current)) {
            if($subscription['subscription_type'] <= $current[0]['subscription_type']) {
                $days_remaining = strtotime($current[0]['end_date']) > time() ? date_diff(date_create($current[0]['end_date']), date_create(date('Y-m-d H:i:s')))->format('%a') : 0;
                $end_date       = $subscription['end_date'];
                $subscription['end_date'] = date('Y-m-d H:i:s', strtotime("{$end_date} + {$days_remaining} days"));
            }
            $result = $sql->sql_update('subscription', $subscription, ['user_id' => $user_id]);
        } else {
            $result = $sql->sql_insert('subscription', $subscription);
        }
        $user = $sql->sql_select_array_query("SELECT email FROM `user` WHERE id = '{$user_id}' LIMIT 1");
        if (count($user) === 1) {
            $this->send_email(
                EMAIL['email'], 
                $user[0]['email'], 
                'Thank you for subscribing on IPTV-Tools.com', 
                $this->subscription_body(date('j M Y', strtotime($subscription['end_date'])))
            );
        }
        return $result;
    }

    // Cancel account - this removes all data for this user!
    function cancel_user($user_id, $password) {
        global $sql;
        $res = $sql->sql_select_array_query("SELECT * FROM `user` WHERE id = '{$user_id}' AND status = 1");
        if (count($res) >= 1) {
            $user    = $res[0];
            $user_id = $user['id'];
            $email   = $user['email'];
            if (hash('sha512', $password) === $user['password'] || $password === $user['password']) {
                // Passwords match - lets delete everything from this user
                // ToDo: Put it all in one query
                $sql->sql_delete('confirm',      ['user_id' => $user_id]);
                $sql->sql_delete('forgot',       ['user_id' => $user_id]);
                $sql->sql_delete('profile',      ['user_id' => $user_id]);
                $sql->sql_delete('subscription', ['user_id' => $user_id]);
                $sql->sql_delete('playlist',     ['user_id' => $user_id]);
                $sql->sql_delete('live',         ['user_id' => $user_id]);
                $sql->sql_delete('movie',        ['user_id' => $user_id]);
                $sql->sql_delete('episodes',     ['user_id' => $user_id]);
                $sql->sql_delete('series_tmdb',  ['user_id' => $user_id]);
                $sql->sql_delete('user',         ['id'      => $user_id]);
                $this->send_email(
                    EMAIL['email'], 
                    $email, 
                    'We are sad to see you go.', 
                    $this->account_canceled_body()
                );
                return true;
            }
        }
        return false;
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

    // Confirm Email Body
    function confirm_email_body ($email, $code) {
        $html = file_get_contents(SITE_ROOT . "/common/email/account-verification.html");
        $html = str_replace('___EMAIL___', $email, $html);
        return str_replace('___CODE___', $code, $html);
    }

    // Reset Password
    function reset_password_body ($password) {
        $html = file_get_contents(SITE_ROOT . "/common/email/reset-password.html");
        return str_replace('___PASSWORD___', $password, $html);
    }

    // Ticket response
    function ticket_repsonse_body ($message) {
        $html = file_get_contents(SITE_ROOT . "/common/email/ticket-response.html");
        return str_replace('___MESSAGE___', $message, $html);
    }

    // Subscription
    function subscription_body ($enddate) {
        $html = file_get_contents(SITE_ROOT . "/common/email/subscription.html");
        return str_replace('___ENDDATE___', $enddate, $html);
    }

    // Account canceled
    function account_canceled_body () {
        return file_get_contents(SITE_ROOT . "/common/email/account-removed.html");
    }

    // Send email
    function send_email ($from, $to, $subject, $body) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, EMAIL['url']);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            "from" => [
                "email" => $from
            ],
            "to" => [
                ["email" => $to]
            ],
            "subject" => $subject,
            "html"    => $body,
            "text"    => $body
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "X-Requested-With: XMLHttpRequest",
            "Authorization: " . EMAIL['auth'] ,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

}