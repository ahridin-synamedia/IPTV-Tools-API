<?php

/**********************************************************************************/
/*																				  */
/*				toolbox.php					  									  */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 31/05/2021    								  		  */
/*				Version	: 1.0													  */
/*																				  */
/**********************************************************************************/

// Get array of command line arguments
function arguments ($argv) {
    $_ARG = array();
    foreach ($argv as $arg) {
	    if (preg_match('/--([^=]+)=(.*)/', $arg, $reg)) {
	        $_ARG[$reg[1]] = $reg[2];
	    } elseif (preg_match('/-([a-zA-Z0-9])/' ,$arg, $reg)) {
	        $_ARG[$reg[1]] = 'true';
	    }
    }
  return $_ARG;
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

// Multiple curl requests simultaniously
function curl_multi_http_get ($urls = array(), $useragent = 'Mozilla/5.0 like Gecko', $headers = []) {
    $url_count   = count($urls);
    $curl_array  = array();
    $curl_master = curl_multi_init();

    for ($i = 0; $i < $url_count; $i++) {
        $curl_array[$i] = curl_init();
        curl_setopt($curl_array[$i], CURLOPT_URL, $urls[$i]);
        curl_setopt($curl_array[$i], CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_array[$i], CURLOPT_USERAGENT, $useragent);
        curl_setopt($curl_array[$i], CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_array[$i], CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl_array[$i], CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl_array[$i], CURLOPT_CONNECTTIMEOUT, 0); 
		curl_setopt($curl_array[$i], CURLOPT_TIMEOUT, 400);
        curl_multi_add_handle($curl_master, $curl_array[$i]);
    }

    do {
        curl_multi_exec($curl_master, $running);
    } while ($running > 0);

    $output = array();
    for ($i = 0; $i < $url_count; $i++) {
        $content    = curl_multi_getcontent($curl_array[$i]);
        $output[$i] = is_json($content) ? json_decode($content, true) : $content;
    }

    return $output;
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
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0); 
	curl_setopt($ch, CURLOPT_TIMEOUT, 400);
    $output = curl_exec($ch);
    curl_close($ch);
    if (!is_json($output)) {
        $output = file_get_contents($url);
    }
    return is_json($output) ? json_decode($output, true) : $output;
}

// Format output as html for easier reading
function print_formatted_output ($text1, $text2) {
    print "<p class=\"my-0\"><span class=\"font-weight-bold\">{$text1}</span> {$text2}</p>";
}