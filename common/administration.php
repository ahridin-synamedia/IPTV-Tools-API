<?php

/**********************************************************************************/
/*																				  */
/*				administration.php 					  							  */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 24/04/2021									  		  */
/*				Version	: 1.0													  */
/*																				  */
/**********************************************************************************/

class administration {

	// User class constructor
	function __construct () {
		//
    }

    // Get users
    function users () {
        global $sql;
        return $sql->sql_select_array_query("SELECT * FROM `user` ORDER BY id DESC");
    }

    // Insert new user
    function insert_user ($user) {
        global $sql;
        $user['password'] = hash('sha512', $user['password']);
        if ($sql->sql_insert('user', $user)) {
            return $sql->last_insert_id();
        }
    }

    // Update user information
    function update_user ($user, $user_id) {
        global $sql;
        $user['password'] = hash('sha512', $user['password']);
        return $sql->sql_update('user', $user, [
            'id' => $user_id
        ]);
    }

    // Delete user
    function delete_user ($user_id) {
        global $sql;
        return $sql->sql_delete('user', [
            'id' => $user_id
        ]);
    }

    // Get profiles
    function profiles () {
        global $sql;
        return $sql->sql_select_array_query("SELECT * FROM `profile` ORDER BY id DESC");
    }

    // Get profile
    function profile ($id) {
        global $sql;
        return $sql->sql_select_array_query("SELECT * FROM `profile` WHERE user_id = {$id} ORDER BY id DESC");
    }

    // Insert new profile
    function insert_profile ($profile) {
        global $sql;
        if ($sql->sql_insert('profile', $profile)) {
            return $sql->last_insert_id();
        }
    }

    // Update profile
    function update_profile ($profile, $id) {
        global $sql;
        return $sql->sql_update('profile', $profile, [
            'id' => $id
        ]);
    }

    // Delete profile
    function delete_profile ($id) {
        global $sql;
        return $sql->sql_delete('profile', [
            'id' => $id
        ]);
    }

    // Get subscriptions
    function subscriptions () {
        global $sql;
        return $sql->sql_select_array_query("SELECT * FROM `subscription` ORDER BY id DESC");
    }

    // Insert new subscription
    function insert_subscription ($subscription) {
        global $sql;
        if ($sql->sql_insert('subscription', $subscription)) {
            return $sql->last_insert_id();
        }
    }

    // Update subscription
    function update_subscription ($subscription, $id) {
        global $sql;
        return $sql->sql_update('subscription', $subscription, [
            'id' => $id
        ]);
    }

    // Delete subscription
    function delete_subscription ($id) {
        global $sql;
        return $sql->sql_delete('subscription', [
            'id' => $id
        ]);
    }

    // Get logins
    function logins () {
        global $sql;
        return $sql->sql_select_array_query("SELECT * FROM `login` ORDER BY id DESC");
    }

    // Delete login
    function delete_login ($id) {
        global $sql;
        return $sql->sql_delete('login', [
            'id' => $id
        ]);
    }

    // Get confirms
    function confirms () {
        global $sql;
        return $sql->sql_select_array_query("SELECT * FROM `confirm` ORDER BY id DESC");
    }

    // Delete confirm
    function delete_confirm ($id) {
        global $sql;
        return $sql->sql_delete('confirm', [
            'id' => $id
        ]);
    }

    // Get forgots
    function forgots () {
        global $sql;
        return $sql->sql_select_array_query("SELECT * FROM `forgot` ORDER BY id DESC");
    }

    // Delete forgot
    function delete_forgot ($id) {
        global $sql;
        return $sql->sql_delete('forgot', [
            'id' => $id
        ]);
    }

    // Get payments
    function payments () {
        global $sql;
        return $sql->sql_select_array_query("SELECT * FROM `payment` ORDER BY id DESC");
    }

    // Delete payment
    function delete_payment ($id) {
        global $sql;
        return $sql->sql_delete('payment', [
            'id' => $id
        ]);
    }

    // Get invoices
    function invoices () {
        global $sql;
        return $sql->sql_select_array_query("SELECT * FROM `invoice` ORDER BY id DESC");
    }

    // Delete invoice
    function delete_invoice ($id) {
        global $sql;
        return $sql->sql_delete('invoice', [
            'id' => $id
        ]);
    }

    // Get financial statistics
    function financial_statistics () {
        global $sql;
        if (date('m') == 1) {
            $sales_last_month   = $sql->sql_select_array_query("SELECT ANY_VALUE(DAYOFMONTH(payment.payment_date)) as day, count(*) as sales FROM payment WHERE MONTH(payment.payment_date) = MONTH(CURRENT_DATE()) -1  AND YEAR(payment.payment_date) = YEAR(CURRENT_DATE()) -1 GROUP BY DATE(payment.payment_date)");
            $revenue_last_month = $sql->sql_select_array_query("SELECT ANY_VALUE(DAYOFMONTH(payment.payment_date)) as day, SUM(payment.amount) as revenue FROM payment WHERE MONTH(payment.payment_date) = MONTH(CURRENT_DATE()) -1 AND YEAR(payment.payment_date) = YEAR(CURRENT_DATE()) -1 GROUP BY DATE(payment.payment_date)");
        } else {
            $sales_last_month   = $sql->sql_select_array_query("SELECT ANY_VALUE(DAYOFMONTH(payment.payment_date)) as day, count(*) as sales FROM payment WHERE MONTH(payment.payment_date) = MONTH(CURRENT_DATE()) -1  AND YEAR(payment.payment_date) = YEAR(CURRENT_DATE()) GROUP BY DATE(payment.payment_date)");
            $revenue_last_month = $sql->sql_select_array_query("SELECT ANY_VALUE(DAYOFMONTH(payment.payment_date)) as day, SUM(payment.amount) as revenue FROM payment WHERE MONTH(payment.payment_date) = MONTH(CURRENT_DATE()) -1 AND YEAR(payment.payment_date) = YEAR(CURRENT_DATE()) GROUP BY DATE(payment.payment_date)");
        }
        $sales_current_month    = $sql->sql_select_array_query("SELECT ANY_VALUE(DAYOFMONTH(payment.payment_date)) as day, count(*) as sales FROM payment WHERE MONTH(payment.payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment.payment_date) = YEAR(CURRENT_DATE()) GROUP BY DATE(payment.payment_date)");
        $revenue_current_month  = $sql->sql_select_array_query("SELECT ANY_VALUE(DAYOFMONTH(payment.payment_date)) as day, SUM(payment.amount) as revenue FROM payment WHERE MONTH(payment.payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment.payment_date) = YEAR(CURRENT_DATE()) GROUP BY DATE(payment.payment_date)");
        
        $sales_current_year     = $sql->sql_select_array_query("SELECT month(payment.payment_date) as month, count(*) as sales FROM payment WHERE YEAR(payment.payment_date) = YEAR(CURRENT_DATE()) GROUP BY MONTH(payment.payment_date)");
        $revenue_current_year   = $sql->sql_select_array_query("SELECT month(payment.payment_date) as month, SUM(payment.amount) as revenue FROM payment WHERE YEAR(payment.payment_date) = YEAR(CURRENT_DATE()) GROUP BY MONTH(payment.payment_date)");
        $sales_last_year        = $sql->sql_select_array_query("SELECT month(payment.payment_date) as month, count(*) as sales FROM payment WHERE YEAR(payment.payment_date) = YEAR(CURRENT_DATE()) -1 GROUP BY MONTH(payment.payment_date)");
        $revenue_last_year      = $sql->sql_select_array_query("SELECT month(payment.payment_date) as month, SUM(payment.amount) as revenue FROM payment WHERE YEAR(payment.payment_date) = YEAR(CURRENT_DATE()) -1 GROUP BY MONTH(payment.payment_date)");

        return [
            'sales' => [
                'last_month'    => $sales_last_month,
                'current_month' => $sales_current_month,
                'last_year'     => $sales_last_year,
                'current_year'  => $sales_current_year
            ],
            'revenue' => [
                'last_month'    => $revenue_last_month,
                'current_month' => $revenue_current_month,
                'last_year'     => $revenue_last_year,
                'current_year'  => $revenue_current_year
            ]
        ];
    }

    // Get tickets
    function tickets ($status = 0) {
        global $sql;
        return $sql->sql_select_array_query("SELECT * FROM `tickets` WHERE ticket_status = {$status} ORDER BY id DESC");
    }

    // Insert new ticket
    function insert_ticket ($ticket) {
        global $sql;
        if ($sql->sql_insert('tickets', $ticket)) {
            return $sql->last_insert_id();
        }
    }

    // Update ticket
    function update_ticket ($ticket, $id) {
        global $sql;
        unset($ticket['last_updated']);
        $sql->sql_update('tickets', $ticket, [
            'id' => $id
        ]);
        return $sql->sql_last_error();
    }

    // Close ticket
    function close_ticket ($id) {
        global $sql;
        return $sql->sql_update('tickets', [
            'ticket_status' => 2
        ], [
            'id' => $id
        ]);
    }

    // Delete ticket
    function delete_ticket ($id) {
        global $sql;
        return $sql->sql_delete('tickets', [
            'id' => $id
        ]);
    }

    // Get app xmltv sources
    function xmltv () {
        global $sql;
        return $sql->sql_select_array_query("SELECT *, (SELECT count(*) FROM xmltv_stations WHERE xmltv_source_id = s.id) AS stations FROM xmltv_source s ORDER BY id ASC");
    }

    // Insert new app xmltv source
    function insert_xmltv ($xmltv) {
        global $sql;
        if ($sql->sql_insert('xmltv_source', $xmltv)) {
            return $sql->last_insert_id();
        }
    }

    // Update app xmltv source
    function update_xmltv ($xmltv, $id) {
        global $sql;
        unset($xmltv['stations']);
        return $sql->sql_update('xmltv_source', $xmltv, [
            'id' => $id
        ]);
    }

    // Delete app xmltv source
    function delete_xmltv ($id) {
        global $sql;
        // Programmes
        $sql->sql_delete('xmltv_programmes', [
            'xmltv_source_id' => $id
        ]);
        // Stations
        $sql->sql_delete('xmltv_stations', [
            'xmltv_source_id' => $id
        ]);
        // Source
        return $sql->sql_delete('xmltv_source', [
            'id' => $id
        ]);
    }

    // Update source (sync playlist) - add to table
    function sync_xmltv ($id) {
        global $sql;
        return $sql->sql_query("INSERT INTO `xmltv_sync` (`xmltv_source_id`, `active`) VALUES('{$id}', false) ON DUPLICATE KEY UPDATE `xmltv_source_id` = '{$id}'");
    }

    // Check if there is a query waiting for syncing xmltv source
    function get_waiting_xmltv () {
        global $sql;
        return $sql->sql_select_array_query("SELECT count(*) as active FROM `xmltv_sync`")[0]['active'] > 0;
    }

    function getSymbolByQuantity($bytes) {
        $symbols = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $exp = floor(log($bytes)/log(1024));
        return sprintf('%.2f '.$symbols[$exp], ($bytes/pow(1024, floor($exp))));
    }

    // Dashboard server
    function dashboard_server () {
        $cpu_cores = intval(shell_exec('cat /proc/cpuinfo | grep "^processor" | wc -l'));
        $cpu_load  = intval(sys_getloadavg()[0] * 100 / $cpu_cores);

        $disk_total      = disk_total_space('/');
        $disk_total_free = disk_free_space('/');
        
        $net_rx_1 = trim(file_get_contents("/sys/class/net/eth0/statistics/rx_bytes"));
        $net_tx_1 = trim(file_get_contents("/sys/class/net/eth0/statistics/tx_bytes"));
        sleep(1);
        $net_rx_2 = trim(file_get_contents("/sys/class/net/eth0/statistics/rx_bytes"));
        $net_tx_2 = trim(file_get_contents("/sys/class/net/eth0/statistics/tx_bytes"));

        $contents = file_get_contents('/proc/meminfo');
        preg_match_all('/(\w+):\s+(\d+)\s/', $contents, $matches);
        $info = array_combine($matches[1], $matches[2]);

        return [
            'cpu' => [
                'cores' => $cpu_cores,
                'load'  => $cpu_load > 100 ? 100 : $cpu_load
            ],
            'memory' => [
                'total'              => $this->getSymbolByQuantity($info['MemTotal'] * 1024),
                'total_free'         => $this->getSymbolByQuantity($info['MemFree'] * 1024),
                'total_used'         => $this->getSymbolByQuantity(($info['MemTotal'] - $info['MemAvailable']) * 1024),
                'total_used_percent' => floor(intval(($info['MemTotal'] - $info['MemAvailable']) * 1024) / ($info['MemTotal']  * 1024) * 100)
            ],
            'hdd' => [
                'total'              => $this->getSymbolByQuantity($disk_total),
                'total_free'         => $this->getSymbolByQuantity($disk_total_free),
                'total_used'         => $this->getSymbolByQuantity($disk_total - $disk_total_free),
                'total_used_percent' => floor((($disk_total - $disk_total_free) / $disk_total) * 100)
            ],
            'network' => [
                'rx'      => round(trim(file_get_contents("/sys/class/net/eth0/statistics/rx_bytes")) / 1024/ 1024/ 1024, 2),
                'tx'      => round(trim(file_get_contents("/sys/class/net/eth0/statistics/tx_bytes")) / 1024/ 1024/ 1024, 2),
                'send'    => round(($net_tx_2 - $net_tx_1) / 1024 * 0.0078125, 2),
                'receive' => round(($net_rx_2 - $net_rx_1) / 1024 * 0.0078125, 2)
            ],
            'info' => $info
        ];
    }

    // Get database info
    function dashboard_database () {
        global $sql;
        return [
            'columns' => $sql->sql_select_array_query("SELECT COUNT(*) AS columns FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'iptvtool_app'")[0]['columns'],
            'rows'    => $sql->sql_select_array_query("SELECT SUM(table_rows) as 'rows' FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'iptvtool_app'")[0]['rows'],
            'tables'  => $sql->sql_select_array_query("SELECT count(*) AS tables FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'iptvtool_app'")[0]['tables'],
            'size'    => $this->getSymbolByQuantity($sql->sql_select_array_query("SELECT SUM( data_length + index_length) AS 'size' FROM information_schema.TABLES WHERE table_schema='iptvtool_app' GROUP BY table_schema")[0]['size'])
        ];
    }

    // Get schedules
    function schedules () {
        global $sql;
        return $sql->sql_select_array_query("SELECT * FROM `scheduler` ORDER BY id ASC");
    }

    // Insert new schedule
    function insert_schedule ($schedule) {
        global $sql;
        if ($sql->sql_insert('scheduler', $schedule)) {
            return $sql->last_insert_id();
        } else {
            return $sql->sql_last_error();
        }
    }

    // Update schedule
    function update_schedule ($schedule, $id) {
        global $sql;
        return $sql->sql_update('scheduler', $schedule, [
            'id' => $id
        ]);
    }

    // Delete schedule
    function delete_schedule ($id) {
        global $sql;
        return $sql->sql_delete('scheduler', [
            'id' => $id
        ]);
    }

    // Execute script and return response
    function execute_schedule ($id) {
        global $sql;
        $schedule = $sql->sql_select_array_query("SELECT * FROM `scheduler` WHERE id = '{$id}' LIMIT 1");
        if (count($schedule) === 0) {
            return false;
        }
        if (file_exists(strtok($schedule[0]['command'],  ' ')) !== false) {
            return shell_exec('/usr/local/cpanel/3rdparty/bin/php ' . $schedule[0]['command'] . ' 2>&1');
        } else {
            return 'File not found! Filename: ' . $schedule[0]['command'];
        }
    }

}

?>