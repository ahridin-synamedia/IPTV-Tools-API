<?php

/**********************************************************************************/
/*																				  */
/*				router.php 					  									  */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 12/04/2021									  		  */
/*				Version	: 1.3													  */
/*																				  */
/**********************************************************************************/

class routeUrl {
	private $root_segment;

	var $site_path;

	// routeUrl class constructor
	function __construct ($site_path, $root_segment) {
		$this->site_path 	= $this->remove_last_slash($site_path);
		$this->root_segment	= $root_segment;
	}

	// Return the full url
	function __toString () {
		return $this->$site_path;
	}

	// Add trailing backslash to the end of the url
	private function add_slash ($string) {
		if($string[mb_strlen($string) -1] != '/') {
			$string .= '/';
		}
		return $string;
	}

	// Remove trailing backslash from url
	private function remove_last_slash ($string) {
		if($string[mb_strlen($string) -1] == '/') {
			$string = rtrim($string, '/');
		}
		return $string;
	}

	// Is input JSON
	private function is_json ($string) {
		if (is_string($string)) {
			json_decode($string);
			return (json_last_error() == JSON_ERROR_NONE);
		} else {
			return false;
		}
	}

	// Get Authorization header
	private function getAuthorizationHeader () {
        $headers = null;
		if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
			$headers = trim($_SERVER['HTTP_AUTHORIZATION']);
		}
        return $headers;
	}

	// Get IP Address for request
	function ip_address () {
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		    return $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		    return $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
		    return $_SERVER['REMOTE_ADDR'];
		}
	}

	// Get request method
	function request_method () {
		return $_SERVER['REQUEST_METHOD'];
	}

	// Redirect page function.
	function redirect ($url, $statusCode = 303) {
	   header('Location: ' . $url, true, $statusCode);
	   die();
	}

	// Get segment from url array
	function segment ($segment, $upper = false, $lower = false) {
		$seg = $this->root_segment + $segment;
		$url = str_replace($this->site_path, '', $_SERVER['REQUEST_URI']);
		$url = explode('/', $url);
		if(isset($url[$seg])) {
			if ($upper === true) {
				return strtoupper($url[$seg]);
			} elseif ($lower === true) {
				return strtolower($url[$seg]);
			} else {
				return $url[$seg];
			}
		} else {
			return false;
		}
	}

	// Return array with segments
	function segments () {
		$url = str_replace($this->site_path, '', $_SERVER['REQUEST_URI']);
		$url = explode('/', $url);
		return $url;
	}

	// Raw PHP POST / PUT data
	function raw_post_data () {
		return file_get_contents('php://input');
	}

	// Array - parsed JSON POST / PUT data
	function array_post_data () {
		return $this->is_json(file_get_contents('php://input')) ? json_decode(file_get_contents('php://input'), true) : false;
	}

	// Return json formatted string
	function json_response ($data = null, $status = true, $code = 200) {
		header_remove();

		http_response_code($code);
		header('Content-Type: application/json');

		$status_header = [
	    200 => '200 OK',
	    201 => '201 Created',
	    202 => '202 Accepted',
	    204 => '204 No Content',

	    400 => '400 Bad Request',
	    401 => '401 Unauthorized',
	    404 => '404 Not Found',
	    405 => '405 Method Not Allowed',
	    
	    500 => '500 Internal Server Error'
	   ];

		header('Status: '.$status_header[$code]);

		echo json_encode([
	    'status'	=> $status,
	    'data' 		=> $data
	  ]);
	}

	// Return invalid route
	function invalid_route () {
		echo $this->json_response('Invalid route!', 404);
	}

	// Get OS from useragent string
	function useragent_os () {
		$os_array = [
			'/windows nt 10/i'		=>  'Windows 10',
			'/windows nt 6.3/i'		=>  'Windows 8.1',
			'/windows nt 6.2/i'		=>  'Windows 8',
			'/windows nt 6.1/i'		=>  'Windows 7',
			'/windows nt 6.0/i'		=>  'Windows Vista',
			'/windows nt 5.2/i'		=>  'Windows Server 2003/XP x64',
			'/windows nt 5.1/i'		=>  'Windows XP',
			'/windows xp/i'			=>  'Windows XP',
			'/windows nt 5.0/i'		=>  'Windows 2000',
			'/windows me/i'			=>  'Windows ME',
			'/win98/i'				=>  'Windows 98',
			'/win95/i'				=>  'Windows 95',
			'/win16/i'				=>  'Windows 3.11',
			'/macintosh|mac os x/i'	=>  'Mac OS X',
			'/mac_powerpc/i'		=>  'Mac OS 9',
			'/linux/i'				=>  'Linux',
			'/ubuntu/i'				=>  'Ubuntu',
			'/iphone/i'				=>  'iPhone',
			'/ipod/i'				=>  'iPod',
			'/ipad/i'				=>  'iPad',
			'/android/i'			=>  'Android',
			'/blackberry/i'			=>  'BlackBerry',
			'/webos/i'				=>  'Mobile'
		];
		$platform = 'Unknown';
		foreach ($os_array as $regex => $value) {
			if (preg_match($regex, $_SERVER['HTTP_USER_AGENT'])) {
				$platform = $value;
				break;
			}
		}
		return $platform;
	}

	// Get browser from useragent string
	function useragent_browser ($ua) {
		$browser_array = [
			'/msie/i'		=> 'Internet Explorer',
			'/firefox/i'	=> 'Firefox',
			'/chrome/i'		=> 'Chrome',
			'/safari/i'		=> 'Safari',
			'/edge/i'		=> 'Edge',
			'/opera/i'		=> 'Opera',
			'/netscape/i'	=> 'Netscape',
			'/maxthon/i'	=> 'Maxthon',
			'/konqueror/i'	=> 'Konqueror',
			'/mobile/i'		=> 'Mobile Browser'
		];
		$browser = 'Unknown';
		foreach ($browser_array as $regex => $value) {
			if (preg_match($regex, $_SERVER['HTTP_USER_AGENT'])) {
				$browser = $value;
				break;
			}
		}
		return $browser;
	}
	
	// Get JWT Token from header
	function getJWT() {
		$headers = $this->getAuthorizationHeader();
		if (!empty($headers)) {
			if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
				return $matches[1];
			}
		}
		return null;
	}
}

?>