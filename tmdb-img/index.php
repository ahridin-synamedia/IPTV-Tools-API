<?php

/************************************************************************************/
/*																					*/
/*				index.php [ IPTV-Tools TMDB Image API]								*/
/*																					*/
/*				Author	: Ernst Reidinga											*/
/*				Date 	: 17/05/2021												*/
/*				Version	: 1.0														*/
/*																					*/
/************************************************************************************/

function file_contents_exist($url) {
    $header = substr(get_headers($url)[0], 9, 3);
    return $header >= 200 && $header < 400;
}

$image = $_SERVER['REQUEST_URI'];
$url   = "https://www.themoviedb.org/t/p/original{$image}";
if (file_contents_exist($url)) {
    $options = array('http' => array('user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36'));
    $context = stream_context_create($options);
    $f = file_get_contents($url, false, $context);
    $pattern = "/^content-type\s*:\s*(.*)$/i";
    if (($header = array_values(preg_grep($pattern, $http_response_header))) && (preg_match($pattern, $header[0], $match) !== false)) {
        $content_type = $match[1];
        header("Content-Type: {$content_type}");
    }
    echo $f;
} else {
    header("HTTP/1.0 404 Not Found");
}
