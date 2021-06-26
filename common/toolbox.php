<?php

/**********************************************************************************/
/*																				  */
/*				toolbox.php					  									  */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 04/05/2021    								  		  */
/*				Version	: 1.0													  */
/*																				  */
/**********************************************************************************/

if (!function_exists('str_putcsv')) {
	function str_putcsv($input, $delimiter = ',', $enclosure = '"') {
		$fp = fopen('php://temp', 'r+');
		fputcsv($fp, $input, $delimiter, $enclosure);
		rewind($fp);
		$data = fread($fp, 1048576);
		fclose($fp);
		return rtrim($data, "\n") . PHP_EOL;
	}
}

class toolBox {

	// toolBox class constructor.
	function __construct () {
		
	}

	// Is given variable JSON string.
	function is_json ($string) {
		if (is_string($string)) {
			json_decode($string);
			return (json_last_error() == JSON_ERROR_NONE);
		} else {
			return false;
		}
	}

	// CURL HTTP.
	function curl_http_get ($url, $useragent = 'Mozilla/5.0 like Gecko', $headers = []) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
	}

	// CURL HTTP JSON.
	function curl_http_get_json ($url, $useragent = 'Mozilla/5.0 like Gecko', $headers = [], $array = true) {
		$res = $this->curl_http_get($url, $useragent, $headers);
		if ($this->is_json($res)) {
		return json_decode($res, $array);
		} else {
		return json_decode('[]', $array);
		}
	}

	// Get genres
	function tmdb_genres ($path, $language) {
		$api_key = TMDB_API_KEY;
        $url = "https://api.themoviedb.org/3/genre/{$path}/list?api_key={$api_key}&language={$language}";
        return $this->curl_http_get_json($url)['genres'];
	}

	// Playlist AUTH (Xtream)
	function playlist_auth ($host, $port, $username, $password, $full = false) {
		$url = !empty($port) ? "http://{$host}:{$port}/player_api.php?username={$username}&password={$password}" : "http://{$host}/player_api.php?username={$username}&password={$password}";
		$res = $this->curl_http_get_json($url);
		if ($full === true) {
			return $res;
		} else {
			return isset($res['user_info']) ? $res['user_info'] : ['user_info' => ['auth' => 0]];
		}
	}

	// Playlist Groups (Xtream)
	function playlist_load_groups ($host, $port, $username, $password) {
        return ['live' => $this->curl_http_get_json(
            !empty($port) ?
            "http://{$host}:{$port}/player_api.php?username={$username}&password={$password}&action=get_live_categories" : 
            "http://{$host}/player_api.php?username={$username}&password={$password}&action=get_live_categories"
        ),
        'movies' => $this->curl_http_get_json(
            !empty($port) ?
            "http://{$host}:{$port}/player_api.php?username={$username}&password={$password}&action=get_vod_categories" : 
            "http://{$host}/player_api.php?username={$username}&password={$password}&action=get_vod_categories"
        ),
        'series' => $this->curl_http_get_json(
            !empty($port) ?
            "http://{$host}:{$port}/player_api.php?username={$username}&password={$password}&action=get_series_categories" : 
            "http://{$host}/player_api.php?username={$username}&password={$password}&action=get_series_categories"
        )];
	}

	// Random string generator
	function generate_string($input, $strength = 6) {
		$input_length = strlen($input);
		$random_string = '';
		for($i = 0; $i < $strength; $i++) {
			$random_character = $input[mt_rand(0, $input_length - 1)];
			$random_string .= $random_character;
		}
		return $random_string;
	}
	function playlist_user_pass_exists ($username, $password) {
		global $sql;
		return $sql->sql_select_array_query("SELECT count(*) as `exists` FROM `playlist` WHERE BINARY api_username = '{$username}' AND BINARY api_password = '{$password}'")[0]['exists'] > 0;
	}

	// Create random password for use in Xtream API / M3U
	function playlist_random_password ($username) {
		do {
			$password = $this->generate_string("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ");
		} while ($this->playlist_user_pass_exists($username, $password));
		return $password;
	}

	// Get random radiobrowser server
	function random_radiobrowser_server () {
		return $this->curl_http_get_json("http://all.api.radio-browser.info/json/servers", "iptv-tools.com");
	}
	
	// Proxy Audio/Video stream so that we can play video's that are not from our own server
	function find_on_radiobrowser ($search) {
		$server = $this->random_radiobrowser_server()[0]["name"];
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://{$server}/json/stations/search");
		curl_setopt($ch, CURLOPT_USERAGENT, 'iptv-tools.com');
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
			"offset"     => 0,
			"limit"      => 50,
			"name"       => $search,
			"hidebroken" => true,
			"order"      => "clickcount",
			"reverse"    => true
		]));
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			"Content-Type: application/json"
		]);
		$res = curl_exec($ch);
		curl_close($ch);
		return $this->is_json($res) ? json_decode($res) : [];
	}

	// Extract soundcloud client_id from soundcloud.com
    function soundcloud_clientid () {
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.87 Safari/537.36';
        $html = $this->curl_http_get('https://soundcloud.com/', $ua);
        preg_match_all(
            '/(?<=src=")(.*?)(?="><\/script>)/i',
            $html,
            $matches
        );
        if (isset($matches[0]) && is_array($matches[0])) {
            $js = $this->curl_http_get($matches[0][count($matches[0]) -1], $ua);
            preg_match_all(
                '/(?<=,client_id:")(.*?)(?=",)/i',
                $js,
                $matches
            );
            if (isset($matches[0]) && isset($matches[0][0])) {
                return $matches[0][0];
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    // Get soundcloud track info
    function soundcloud ($url, $client_id) {
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.87 Safari/537.36';
        $url = urlencode($url);
        $res = $this->curl_http_get("https://api-mobi.soundcloud.com/resolve?permalink_url={$url}&client_id={$client_id}&format=json", $ua);
        return $this->is_json($res) ? json_decode($res) : $res;
	}

	// Get YT Video page - can not get it directly with ajax
	function youtube_page ($video_id) {
		$ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.87 Safari/537.36';
        $str = $this->curl_http_get("https://youtube.com/watch?v={$video_id}", $ua);
        echo $str;
	}
	
	// Get Vimeo configuration
	function vimeo_config ($video_id) {
		$ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.87 Safari/537.36';
        $str = $this->curl_http_get("https://player.vimeo.com/video/{$video_id}/config", $ua);
        return json_decode($str);
	}
	
	// Get Dailymotion configuration
	function dailymotion_config ($video_id) {
		$ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.87 Safari/537.36';
        $str = $this->curl_http_get("https://api.dailymotion.com/video/{$video_id}?fields=created_time,title,views_total,duration,thumbnail_1080_url,tags,description", $ua);
        return json_decode($str);
	}

	// Get Ted Talks configuration
	function tedtalks_config ($url) {
		$ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.87 Safari/537.36';
        $html = $this->curl_http_get($url, $ua);
        preg_match_all(
            '/(?<="talkPage\.init",)(.*?)(?=\)<\/script>)/i',
            $html,
            $matches
        );
        return is_array($matches) && isset($matches[0]) && isset($matches[0][0]) ? json_decode($matches[0][0], true)['__INITIAL_DATA__'] : $matches;
    }
	
	// Get XHamster configuration
	function xhamster_config ($url) {
		$ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.87 Safari/537.36';
        $html = $this->curl_http_get($url, $ua);
        preg_match_all(
            '/(?<=initials=)(.*?)(?=;<\/script>)/i',
            $html,
            $matches
        );
        return is_array($matches) && isset($matches[0]) && isset($matches[0][0]) ? json_decode($matches[0][0]) : $matches;
	}

	// Get list of available logo's countries
	function logos_countries () {
		$out = [];
		$dir = new DirectoryIterator('/home/iptvtools/public_html/static');
		foreach ($dir as $fileinfo) {
		    if ($fileinfo->isDir() && !$fileinfo->isDot() && strpos($fileinfo->getFilename(), '.well-known') === false) {
		        array_push($out, $fileinfo->getFilename());
		    }
		}
		return $out;
	}
	
	// Get list of logo's for country
	function logos ($directory) {
		$out = [];
		$dir = sprintf('/home/iptvtools/public_html/static/%s/', $directory);
		if (file_exists($dir)) {
			foreach (glob($dir.'*.{jpg,JPG,jpeg,JPEG,png,PNG}',GLOB_BRACE) as $file){
				array_push($out, [
					'filename'	=> basename($file),
					'label'		=> pathinfo($file, PATHINFO_FILENAME)
				]);
			}	
		}
		return $out;
	}

	// Get list of available tvguide countries
	function tvguide_countries () {
		global $sql;
		$countries = $sql->sql_select_array_query("SELECT DISTINCT country FROM `xmltv_source`");
		foreach ($countries as $country) {
			$result[] = strtolower($country['country']);
		}
		return $result;
	}

	// Get list of channels for country
	function tvguide_ids ($country) {
		global $sql;
		if (strtolower($country) == "all") {
			return $sql->sql_select_array_query("SELECT DISTINCT tvg_id, tvg_name FROM `xmltv_stations` ORDER BY tvg_name ASC");
		} else {
			return $sql->sql_select_array_query("SELECT DISTINCT tvg_id, tvg_name FROM `xmltv_stations` WHERE xmltv_source_id IN (SELECT id FROM `xmltv_source` WHERE country = '{$country}') ORDER BY tvg_name ASC");
		}
	}

	// Echo and add newline
	function echo_newline ($str) {
		echo $str . PHP_EOL;
	}

	// Clean filename so we can use it in the zipfile
	function clean_filename ($filename) {
		return rtrim(mb_ereg_replace("([\.]{2,})", '', mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $filename)), ' (');
	}

	// Export playlist to M3U
	function export_to_m3u ($user_id, $playlist_id) {
		global $sql;	
		// Get playlist and groups
		$playlist = $sql->sql_select_array_query("SELECT * FROM `playlist` WHERE user_id = '{$user_id}' AND id = '{$playlist_id}' LIMIT 1")[0];
		$groups   = $sql->sql_select_array_query("SELECT id, group_type, group_name FROM `groups` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' ORDER by group_order");
		// Set base URL
		$host = $playlist['source_host'];
		$port = $playlist['source_port'];
		$user = $playlist['source_username'];
		$pass = $playlist['source_password'];
		$url  = !empty($port) ? "http://{$host}:{$port}" : "http://{$host}";
		// Set headers
		header('Content-Type: application/mpegurl');
		header('Content-Disposition: attachment; filename=playlist.m3u');
		header('Pragma: no-cache');
		// Create m3u file
		$this->echo_newline("#EXTM3U");
		foreach ($groups as $group) {
			$group_id = $group['id'];
			switch (intval($group['group_type'])) {
				// Live streams
				case 1:
					$streams = $sql->sql_select_array_query("SELECT source_stream_url, source_stream_id, stream_tvg_name, stream_tvg_id, stream_tvg_logo, stream_is_custom FROM `live` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' and group_id = '{$group_id}' ORDER BY stream_order");
					break;
				// Movies
				case 2:
					$streams = $sql->sql_select_array_query("SELECT source_stream_url, source_stream_id, stream_tvg_name, stream_tvg_id, stream_tvg_logo, stream_is_custom, source_container_extension FROM `movie` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' and group_id = '{$group_id}' ORDER BY stream_order");
					break;
				// Series
				case 3:
					$streams = $sql->sql_select_array_query("SELECT source_stream_url, source_stream_id, stream_tvg_name, stream_tvg_id, stream_tvg_logo, stream_is_custom, source_container_extension FROM `episodes` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' and group_id = '{$group_id}' ORDER by stream_order");
					break;
			}
			foreach ($streams as $stream) {
				$tvg_name   = $stream['stream_tvg_name'];
				$tvg_id     = $stream['stream_tvg_id'];
				$tvg_logo   = $stream['stream_tvg_logo'];
				$group_name = $group['group_name'];
				$this->echo_newline("#EXTINF:-1 tvg-id=\"{$tvg_id}\" tvg-name=\"{$tvg_name}\" tvg-logo=\"{$tvg_logo}\" group-title=\"{$group_name}\",{$tvg_name}");
				if (boolval($stream['stream_is_custom'])) {
					$this->echo_newline($stream['source_stream_url']);
				} else {
					$stream_id = $stream['source_stream_id'];
					switch (intval($group['group_type'])) {
						// Live
						case 1:
							$this->echo_newline("{$url}/{$user}/{$pass}/{$stream_id}");
							break;
						// Movie
						case 2:
							$container_extension = $stream["source_container_extension"];
							$this->echo_newline("{$url}/movie/{$user}/{$pass}/{$stream_id}.{$container_extension}");
							break;
						// Series
						case 3:
							$container_extension = $stream["source_container_extension"];
							$this->echo_newline("{$url}/series/{$user}/{$pass}/{$stream_id}.{$container_extension}");
							break;
					}
				}
			}
		}
	}

	// Export playlist to SmartIPTV Txt file
	function export_to_siptv ($user_id, $playlist_id) {
		global $sql;	
		// Get playlist and groups
		$playlist = $sql->sql_select_array_query("SELECT * FROM `playlist` WHERE user_id = '{$user_id}' AND id = '{$playlist_id}' LIMIT 1")[0];
		$groups   = $sql->sql_select_array_query("SELECT id, group_type, group_name, group_parent_code FROM `groups` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' ORDER by group_order");
		// Set base URL
		$host = $playlist['source_host'];
		$port = $playlist['source_port'];
		$user = $playlist['source_username'];
		$pass = $playlist['source_password'];
		$url  = !empty($port) ? "http://{$host}:{$port}" : "http://{$host}";
		// Set headers
		header('Content-Type: text/plain');
		header('Content-Disposition: attachment; filename=playlist.txt');
		header('Pragma: no-cache');
		foreach ($groups as $group) {
			$group_id   	  = $group['id'];
			$group_name 	  = $group['group_name'];
			$group_parentcode = $group['group_parent_code'];
			if (!empty($group_parentcode)) {
				$this->echo_newline("group,{$group_name},{$group_parentcode}");
			} else {
				$this->echo_newline("group,{$group_name}");
			}
			switch (intval($group['group_type'])) {
				// Live streams
				case 1:
					$streams = $sql->sql_select_array_query("SELECT source_stream_url, source_stream_id, stream_tvg_name, stream_tvg_id, stream_tvg_logo, stream_is_custom FROM `live` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' and group_id = '{$group_id}' ORDER BY stream_order");
					break;
				// Movies
				case 2:
					$streams = $sql->sql_select_array_query("SELECT source_stream_url, source_stream_id, stream_tvg_name, stream_tvg_id, stream_tvg_logo, stream_is_custom, source_container_extension FROM `movie` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' and group_id = '{$group_id}' ORDER BY stream_order");
					break;
				// Series
				case 3:
					$streams = $sql->sql_select_array_query("SELECT source_stream_url, source_stream_id, stream_tvg_name, stream_tvg_id, stream_tvg_logo, stream_is_custom, source_container_extension FROM `episodes` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' and group_id = '{$group_id}' ORDER by stream_order");
					break;
			}
			foreach ($streams as $stream) {
				$tvg_name   = $stream['stream_tvg_name'];
				$tvg_id     = empty($stream['stream_tvg_id']) ? "ext" : $stream['stream_tvg_id'];
				$tvg_logo   = $stream['stream_tvg_logo'];				
				if (boolval($stream['stream_is_custom'])) {
					$stream_url = $stream['source_stream_url'];
				} else {
					$stream_id = $stream['source_stream_id'];
					switch (intval($group['group_type'])) {
						// Live
						case 1:
							$stream_url = "{$url}/{$user}/{$pass}/{$stream_id}";
							break;
						// Movie
						case 2:
							$container_extension = $stream["source_container_extension"];
							$stream_url = "{$url}/movie/{$user}/{$pass}/{$stream_id}.{$container_extension}";
							break;
						// Series
						case 3:
							$container_extension = $stream["source_container_extension"];
							$stream_url = "{$url}/series/{$user}/{$pass}/{$stream_id}.{$container_extension}";
							break;
					}
				}
				if (intval($group['group_type']) === 1) {
					$this->echo_newline("{$tvg_id},{$tvg_name},{$stream_url}");
				} else {
					$this->echo_newline("avi,{$tvg_name},{$stream_url}");
				}
			}
		}
	}

	// Export playlist to Bouquet
	function export_to_bouquet ($user_id, $playlist_id) {
		error_reporting(0);
		global $sql;	
		// Get playlist and groups
		$playlist = $sql->sql_select_array_query("SELECT * FROM `playlist` WHERE user_id = '{$user_id}' AND id = '{$playlist_id}' LIMIT 1")[0];
		$groups   = $sql->sql_select_array_query("SELECT id, group_type, group_name FROM `groups` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' ORDER by group_order");
		// Set base URL
		$host = $playlist['source_host'];
		$port = $playlist['source_port'];
		$user = $playlist['source_username'];
		$pass = $playlist['source_password'];
		$url  = !empty($port) ? "http%3a//{$host}%3a{$port}" : "http%3a//{$host}";
		// Create zipfile with bouquets
		$zipfile  = new ZipArchive();
		$filename = tempnam('tmp', 'zip');
		if ($zipfile->open($filename, ZipArchive::CREATE) === TRUE) {
		 	$bouquets_tv = "#NAME Bouquets (TV)";
			foreach ($groups as $group) {
				$group_name = $this->clean_filename($group['group_name']);
				$group_id = $group['id'];
				switch (intval($group['group_type'])) {
					// Live streams
					case 1:
						$group_type = "Live";
						$streams = $sql->sql_select_array_query("SELECT source_stream_url, source_stream_id, stream_tvg_name, stream_is_custom FROM `live` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' and group_id = '{$group_id}' ORDER BY stream_order");
						break;
					// Movies
					case 2:
						$group_type = "Movies";
						$streams = $sql->sql_select_array_query("SELECT source_stream_url, source_stream_id, stream_tvg_name, stream_is_custom, source_container_extension FROM `movie` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' and group_id = '{$group_id}' ORDER BY stream_order");
						break;
					// Series
					case 3:
						$group_type = "Series";
						$streams = $sql->sql_select_array_query("SELECT source_stream_url, source_stream_id, stream_tvg_name, stream_is_custom, source_container_extension FROM `episodes` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' and group_id = '{$group_id}' ORDER by stream_order");
						break;
				}
				$bouquets_tv .= "#SERVICE 1:7:1:0:0:0:0:0:0:0:FROM BOUQUET “userbouquet.{$group_type} {$group_name}.tv” ORDER BY bouquet" . PHP_EOL;
				$bouquet      = "";
				foreach ($streams as $stream) {	
					if (boolval($stream['stream_is_custom'])) {
						$stream_url = str_replace(":", "%3a", $stream['source_stream_url']);
					} else {
						$stream_id = $stream['source_stream_id'];
						switch (intval($group['group_type'])) {
							// Live
							case 1:
								$stream_url = "{$url}/{$user}/{$pass}/{$stream_id}";
								break;
							// Movie
							case 2:
								$container_extension = $stream["source_container_extension"];
								$stream_url = "{$url}/movie/{$user}/{$pass}/{$stream_id}.{$container_extension}";
								break;
							// Series
							case 3:
								$container_extension = $stream["source_container_extension"];
								$stream_url = "{$url}/series/{$user}/{$pass}/{$stream_id}.{$container_extension}";
								break;
						}
					}
					$tvg_name = $stream['stream_tvg_name'];
					$bouquet .= "#DESCRIPTION {$tvg_name}" . PHP_EOL;
					$tvg_name = str_replace(":", "%3a", $stream['stream_tvg_name']);
					$bouquet .= "#SERVICE 1:0:1:1:0:0:0:0:0:0:{$stream_url}:{$tvg_name}" . PHP_EOL;					
				}
				$zipfile->addFromString("{$group_type} {$group_name}.tv", $bouquet);
			}
			$zipfile->addFromString('bouquets.tv', $bouquets_tv);
			$zipfile->close();
			header('Content-Type: application/zip');
			header('Content-Length: ' . filesize($filename));
			header('Content-Disposition: attachment; filename="bouquet.zip"');
			readfile($filename);
			unlink($filename);
		}
	}

	// Export playlist to CSV (Excel)
	function export_to_csv ($user_id, $playlist_id) {
		global $sql;	
		error_reporting(0);		
		// Create zipfile with csv files
		$zipfile  = new ZipArchive();
		$filename = tempnam('tmp', 'zip');
		if ($zipfile->open($filename, ZipArchive::CREATE) === TRUE) {
			// Live streams
			$csv = "tvg-Name,tvg-Id,tvg-Logo" . PHP_EOL;
			$live = $sql->sql_select_array_query("SELECT stream_tvg_name, stream_tvg_id, stream_tvg_logo FROM `live` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' ORDER BY stream_order");
			foreach ($live as $stream) {
				$csv .= str_putcsv($stream);
			}
			$zipfile->addFromString('live.csv', $csv);
			// Movies
			$csv = "Name,Year,TMDB-ID,Poster" . PHP_EOL;
			$movies = $sql->sql_select_array_query("SELECT movie_name, movie_year, tmdb_id, stream_tvg_logo FROM `movie` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND movie_name <> '' AND movie_name IS NOT NULL ORDER BY stream_order");
			foreach ($movies as $stream) {
				$csv .= str_putcsv($stream);
			}
			$zipfile->addFromString('Movies.csv', $csv);
			// Series
			$csv = "Name,Season,Episode,TMDB-ID,Poster" . PHP_EOL;
			$series = $sql->sql_select_array_query("SELECT serie_name, serie_season, serie_episode, tmdb_id, stream_tvg_logo FROM `episodes` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND serie_name <> '' AND serie_name IS NOT NULL ORDER BY stream_order");
			foreach ($series as $stream) {
				$csv .= str_putcsv($stream);
			}
			$zipfile->addFromString('Series.csv', $csv);
			$zipfile->close();
		}
		header('Content-Type: application/zip');
		header('Content-Length: ' . filesize($filename));
		header('Content-Disposition: attachment; filename="export.zip"');
		readfile($filename);
		unlink($filename);
	}

	// Export playlist to JSON
	function export_to_json ($user_id, $playlist_id) {
		global $sql;	
		error_reporting(0);		
		// Create zipfile with json files
		$zipfile  = new ZipArchive();
		$filename = tempnam('tmp', 'zip');
		if ($zipfile->open($filename, ZipArchive::CREATE) === TRUE) {
			// Live streams
			$live = $sql->sql_select_array_query("SELECT stream_tvg_name as 'tvg-name', stream_tvg_id as 'tvg-id', stream_tvg_logo as 'tvg-logo' FROM `live` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' ORDER BY stream_order");
			$zipfile->addFromString('live.json', json_encode($live, JSON_PRETTY_PRINT));
			// Movies
			$movies = $sql->sql_select_array_query("SELECT movie_name as 'name', movie_year as 'year', tmdb_id as 'tmdb-id', stream_tvg_logo as 'poster' FROM `movie` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND movie_name <> '' AND movie_name IS NOT NULL ORDER BY stream_order");
			$zipfile->addFromString('movies.json', json_encode($movies, JSON_PRETTY_PRINT));
			// Series
			$series = $sql->sql_select_array_query("SELECT serie_name as 'name', serie_season as 'season', serie_episode as 'episode', tmdb_id as 'tmdb-id', stream_tvg_logo as 'poster' FROM `episodes` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND serie_name <> '' AND serie_name IS NOT NULL ORDER BY stream_order");
			$zipfile->addFromString('series.json', json_encode($series, JSON_PRETTY_PRINT));
			$zipfile->close();
		}
		header('Content-Type: application/zip');
		header('Content-Length: ' . filesize($filename));
		header('Content-Disposition: attachment; filename="export.zip"');
		readfile($filename);
		unlink($filename);
	}

	// Backup playlist
	function backup_playlist ($user_id, $playlist_id, $live = false, $movies = false, $series = false) {
		global $sql;	
		error_reporting(0);
		ini_set('memory_limit', '4096M');
		ini_set('max_execution_time', '0');
		// Create zipfile with json files
		$zipfile  = new ZipArchive();
		$filename = tempnam('tmp', 'zip');
		if ($zipfile->open($filename, ZipArchive::CREATE) === TRUE) {
			// Playlist
			$zipfile->addFromString('playlist.json', json_encode($sql->sql_select_array_query("SELECT `source_host`, `source_port` FROM `playlist` WHERE user_id = '{$user_id}' AND id = '{$playlist_id}' LIMIT 1")[0]));
			$zipfile->setEncryptionName('playlist.json', ZipArchive::EM_AES_256, ZIP_PASSWORD);
			// Groups
			$zipfile->addFromString('groups.json', json_encode($sql->sql_select_array_query("SELECT `source_category_id`, `source_group_name`, `source_group_order`, `source_group_type`, `group_is_custom`, `group_order`, `group_type`, `group_name`, `group_is_hidden`, `group_parent_code` FROM `groups` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}'")));
			$zipfile->setEncryptionName('groups.json', ZipArchive::EM_AES_256, ZIP_PASSWORD);
			// Live streams
			if ($live === true) {
				$zipfile->addFromString('live.json', json_encode($sql->sql_select_array_query("SELECT `group_id`, `source_stream_id`, `source_stream_url`, `source_tvg_name`, `source_tvg_id`, `source_tvg_logo`, `source_order`, `source_stream_type`, `source_tv_archive`, `source_tv_archive_duration`, `stream_tvg_name`, `stream_tvg_id`, `stream_tvg_logo`, `stream_tvg_chno`, `stream_tvg_shift`, `stream_parent_code`, `stream_audio_track`, `stream_aspect_ratio`, `stream_radio`, `stream_order`, `stream_is_hidden`, `stream_is_custom`, `sync_is_new`, `sync_is_removed` FROM `live` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' ORDER BY id")));
				$zipfile->setEncryptionName('live.json', ZipArchive::EM_AES_256, ZIP_PASSWORD);
			}			
			// Movies
			if ($movies === true) {
				$zipfile->addFromString('movie.json', json_encode($sql->sql_select_array_query("SELECT `group_id`, `source_stream_id`, `source_stream_url`, `source_tvg_name`, `source_tvg_id`, `source_tvg_logo`, `source_order`, `source_stream_type`, `source_container_extension`, `stream_tvg_name`, `stream_tvg_id`, `stream_tvg_logo`, `stream_tvg_chno`, `stream_tvg_shift`, `stream_parent_code`, `stream_audio_track`, `stream_aspect_ratio`, `stream_radio`, `stream_order`, `stream_is_hidden`, `stream_is_custom`, `sync_is_new`, `sync_is_removed`, `movie_name`, `movie_year`, `tmdb_id` FROM `movie` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' ORDER BY id")));
				$zipfile->setEncryptionName('movie.json', ZipArchive::EM_AES_256, ZIP_PASSWORD);
			}
			// Series
			if ($series === true) {
				$zipfile->addFromString('episodes.json', json_encode($sql->sql_select_array_query("SELECT `group_id`, `source_stream_id`, `source_stream_url`, `source_tvg_name`, `source_tvg_id`, `source_tvg_logo`, `source_order`, `source_stream_type`, `source_container_extension`, `source_serie_id`, `stream_tvg_name`, `stream_tvg_id`, `stream_tvg_logo`, `stream_tvg_chno`, `stream_tvg_shift`, `stream_parent_code`, `stream_audio_track`, `stream_aspect_ratio`, `stream_radio`, `stream_order`, `stream_is_hidden`, `stream_is_custom`, `sync_is_new`, `sync_is_removed`, `tmdb_id`, `tmdb_episode_id`, `serie_name`, `serie_season`, `serie_episode`, `serie_trailer` FROM `episodes` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' ORDER BY id")));
				$zipfile->setEncryptionName('episodes.json', ZipArchive::EM_AES_256, ZIP_PASSWORD);
			}
			$zipfile->close();
			header('Content-Type: application/zip');
			header('Content-Length: ' . filesize($filename));
			header('Content-Disposition: attachment; filename="export.zip"');
			readfile($filename);
			unlink($filename);
		}
	}

	// Restore playlist
	function restore_playlist ($user_id, $playlist_id, $filename) {
		global $sql;	
		error_reporting(0);
		ini_set('memory_limit', '4096M');
		ini_set('max_execution_time', '0');
		$result = 0;
		// Open zipfile
		$zipfile = new ZipArchive();
		if ($zipfile->open($filename) && $zipfile->setPassword(ZIP_PASSWORD)) {
			// Load groups
			$groups = json_decode($zipfile->getFromName('groups.json'), true);
			$group_ids = [];
			// Restore groups
			foreach ($groups as $group) {
				$result++;
				$sql->sql_insert_update('groups', $group);
				if ($sql->sql_query($query)) {
					$group_ids[$group['id']] = $sql->last_insert_id();
				}
			}
			// Load live streams
			if ($zipfile->locateName('live.json') !== false) {	
				$live = json_decode($zipfile->getFromName('live.json'), true);
				// Restore live streams
				foreach ($live as $stream) {
					$result++;
					$stream['user_id']     = $user_id;
					$stream['playlist_id'] = $playlist_id;
					$stream['group_id']    = $group_ids[$stream['group_id']];
					$sql->sql_insert_update('live', $stream);
				}
			}
			// Load movies
			if ($zipfile->locateName('movie.json') !== false) {	
				$movies = json_decode($zipfile->getFromName('movie.json'), true);
				// Restore movies
				foreach ($movies as $stream) {
					$result++;
					$stream['user_id']     = $user_id;
					$stream['playlist_id'] = $playlist_id;
					$stream['group_id']    = $group_ids[$stream['group_id']];
					$sql->sql_insert_update('movie', $stream);
				}
			}
			// Load episodes
			if ($zipfile->locateName('live.json') !== false) {	
				$episodes = json_decode($zipfile->getFromName('episodes.json'), true);
				// Restore series
				foreach ($episodes as $stream) {
					$result++;
					$stream['user_id']     = $user_id;
					$stream['playlist_id'] = $playlist_id;
					$stream['group_id']    = $group_ids[$stream['group_id']];
					$sql->sql_insert_update('episodes', $stream);
				}
			}
			$zipfile->close();
		}
		unlink($filename);
		return $result > 0;
	}

	// Backup EPG Codes for live streams
	function backup_epgcodes ($user_id, $playlist_id) {
		global $sql;	
		error_reporting(0);
		ini_set('memory_limit', '4096M');
		ini_set('max_execution_time', '0');
		// Create zipfile with json files
		$zipfile  = new ZipArchive();
		$filename = tempnam('tmp', 'zip');
		if ($zipfile->open($filename, ZipArchive::CREATE) === TRUE) {
			// Live streams
			$zipfile->addFromString('live-epg.json', json_encode($sql->sql_select_array_query("SELECT `source_stream_id`, `source_tvg_name`, `source_tvg_id`, `stream_tvg_name`, `stream_tvg_id`, `stream_is_custom` FROM `live` WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND stream_tvg_id <> '' AND stream_tvg_id IS NOT NULL ORDER BY id")));
			$zipfile->setEncryptionName('live-epg.json', ZipArchive::EM_AES_256, ZIP_PASSWORD);		
			$zipfile->close();
			header('Content-Type: application/zip');
			header('Content-Length: ' . filesize($filename));
			header('Content-Disposition: attachment; filename="export.zip"');
			readfile($filename);
			unlink($filename);
		}
	}

	function restore_epgcodes ($user_id, $playlist_id, $filename) {
		global $sql;	
		error_reporting(0);
		ini_set('memory_limit', '4096M');
		ini_set('max_execution_time', '0');
		$result = 0;
		// Open zipfile
		$zipfile = new ZipArchive();
		if ($zipfile->open($filename) && $zipfile->setPassword(ZIP_PASSWORD)) {
			// Load live streams
			if ($zipfile->locateName('live-epg.json') !== false) {	
				$live = json_decode($zipfile->getFromName('live-epg.json'), true);
				// Restore live streams
				foreach ($live as $stream) {
					$result++;
					$stream_tvg_name  = $stream['stream_tvg_name'];
					$stream_tvg_id    = $stream['stream_tvg_id'];
					$source_stream_id = $stream['source_stream_id'];
					if (boolval($stream['stream_is_custom'])) {
						$sql->sql_query("UPDATE `live` SET `stream_tvg_id`= '{$stream_tvg_id}' WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND stream_tvg_name = '{$stream_tvg_name}'");
					} else {
						$sql->sql_query("UPDATE `live` SET `stream_tvg_id`= '{$stream_tvg_id}' WHERE user_id = '{$user_id}' AND playlist_id = '{$playlist_id}' AND source_stream_id = '{$source_stream_id}'");
					}
				}
			}
			$zipfile->close();
		}
		unlink($filename);
		return $result > 0;
	}

	// User Dashboard Statistics
	function user_statistics ($user_id) {
		global $sql;
		return $sql->sql_select_array_query("SELECT count(*) as 'playlist', (SELECT count(*) FROM live WHERE user_id = '{$user_id}') as 'live', (SELECT count(*) FROM movie WHERE user_id = '{$user_id}') as 'movies', (SELECT count(*) FROM episodes WHERE user_id = '{$user_id}') as 'series' FROM playlist WHERE user_id = '{$user_id}'")[0];
	}

	// Get Dashboard xtream accounts
	function user_xtreamaccounts ($user_id) {
		global $sql;
		$result = $sql->sql_select_array_query("SELECT DISTINCT source_host, source_port, source_username, source_password FROM `playlist` WHERE user_id = '{$user_id}'");
		$output = [];
		foreach ($result as $account) {
			if (!empty($account['source_host']) && !empty($account['source_username']) && !empty($account['source_password'])) {
				$output[] = $this->playlist_auth($account['source_host'], $account['source_port'], $account['source_username'], $account['source_password'], true);
			}
		}
		return $output;
	}

	// Get Dashboard playlists
	function user_playlists ($user_id) {
		global $sql;
		return $sql->sql_select_array_query("SELECT *, (SELECT count(*) FROM live WHERE user_id = '{$user_id}' AND playlist_id = p.id) as 'live', (SELECT count(*) FROM movie WHERE user_id = '{$user_id}' AND playlist_id = p.id) as 'movies', (SELECT count(*) FROM episodes WHERE user_id = '{$user_id}' AND playlist_id = p.id) as 'series', (SELECT count(*) FROM live WHERE user_id = '{$user_id}' AND playlist_id = p.id AND sync_is_new = 1) as 'live-new', (SELECT count(*) FROM movie WHERE user_id = '{$user_id}' AND playlist_id = p.id AND sync_is_new = 1) as 'movie-new', (SELECT count(*) FROM episodes WHERE user_id = '{$user_id}' AND playlist_id = p.id AND sync_is_new = 1) as 'series-new', (SELECT count(*) FROM live WHERE user_id = '{$user_id}' AND playlist_id = p.id AND sync_is_removed = 1) as 'live-removed', (SELECT count(*) FROM movie WHERE user_id = '{$user_id}' AND playlist_id = p.id AND sync_is_removed = 1) as 'movie-removed', (SELECT count(*) FROM episodes WHERE user_id = '{$user_id}' AND playlist_id = p.id AND sync_is_removed = 1) as 'series-removed' FROM `playlist` p WHERE user_id = '{$user_id}'");
	}

}
