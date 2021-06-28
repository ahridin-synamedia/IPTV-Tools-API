<?php

/**********************************************************************************/
/*																				  */
/*				xmltv_sync.php                      							  */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 11/06/2021    								  		  */
/*				Version	: 1.0													  */
/*																				  */
/**********************************************************************************/

include_once 'db_sql.php';
include_once 'const.php';

$sql = $sql = new dbSQL(
	SQL['app']['server'],
	SQL['app']['username'],
	SQL['app']['password'],
	SQL['app']['dbname']
);
$dir   = __DIR__ . "/xmltv/";

/************************************************************************************/
/*																					*/
/*				Set Timezone to Amsterdam									 		*/
/*																					*/
/************************************************************************************/
date_default_timezone_set('Europe/Amsterdam');

/************************************************************************************/
/*																					*/
/*				CURL HTTP FUNCTIONS                             					*/
/*																					*/
/************************************************************************************/
// CURL DOWNLOAD FILE
function download_file ($url, $filename) {
    global $dir;
    file_put_contents($dir.$filename, fopen($url, 'r'));
}

/************************************************************************************/
/*																					*/
/*				EXTRACT FUNCTIONS                                  					*/
/*																					*/
/************************************************************************************/
function extract_xml ($filename) {
    global $dir;
    if (file_exists($dir.$filename)) {
        $xml = simplexml_load_file($dir.$filename);
        // Delete "old" file
        unlink($dir.$filename);
        // Return XML object
        return $xml;
    }
    return null;
}

function extract_gz ($filename) {
    global $dir;
    if (file_exists($dir.$filename)) {
        $extract = shell_exec("gunzip -d -f {$dir}{$filename} 2>&1");
        $path    = pathinfo($filename);
        if (file_exists($dir.$path['filename'])) {
            // Load file to xml object
            $xml = simplexml_load_file($dir.$path['filename']);
            // Delete "old" files
            unlink($dir.$filename);
            unlink($dir.$path['filename']);
            // Return the $xml object
            return $xml;
        }
    }
    return null;
}

function extract_xz ($filename) {
    global $dir;
    if (file_exists($dir.$filename)) {
        $extract = shell_exec("xz -d -k -f {$dir}{$filename} 2>&1");
        $path    = pathinfo($filename);
        if (file_exists($dir.$path['filename'])) {
            // Load file to xml object
            $xml = simplexml_load_file($dir.$path['filename']);
            // Delete "old" files
            unlink($dir.$filename);
            unlink($dir.$path['filename']);
            // Return the $xml object
            return $xml;
        }
    }
    return null;
}

function extract_zip ($filename) {
    global $dir;
    if (file_exists($dir.$filename)) {
        $extract = shell_exec("unzip -o {$dir}{$filename} -d {$dir} 2>&1");
        $path    = pathinfo($filename);
        if (file_exists($dir.$path['filename'].".xml")) {
            // Load file to xml object
            $xml = simplexml_load_file($dir.$path['filename'].".xml");
            // Delete "old" files
            unlink($dir.$filename);
            unlink($dir.$path['filename'].".xml");
            // Return the $xml object
            return $xml;
        }
    }
    return null;
}

/************************************************************************************/
/*																					*/
/*				PARSER FUNCTIONS                                  					*/
/*																					*/
/************************************************************************************/
function xmltv_to_datetime ($date) {
    return DateTime::createFromFormat("YmdHis O", $date);
}

function extract_year ($text) {
	if (preg_match("(19\d{2}|20(?:0\d|1[0-9]|2[0-9]))", $text, $n)) {
		return $n[0];
	}
	return '';
}

function extract_series_info ($text) {
	if (preg_match("'^(.+)\.*(19\d{2}|20(?:0\d|1[0-9]|2[0-9])).*S([0-9]+).*E([0-9]+).*$'i", $text, $n)) {
		return [
			'season'  => str_pad(intval($n[3], 10), 2, '0', STR_PAD_LEFT),
	    	'episode' => str_pad(intval($n[4], 10), 2, '0', STR_PAD_LEFT)
		];
	} elseif (preg_match("'^(.+)\.*S([0-9]+).*E([0-9]+).*$'i", $text, $n)) {
	    return [
	    	'season'  => str_pad(intval($n[2], 10), 2, '0', STR_PAD_LEFT),
	    	'episode' => str_pad(intval($n[3], 10), 2, '0', STR_PAD_LEFT)
	    ];
    }
    return false;
}

/************************************************************************************/
/*																					*/
/*				MAIN FUNCTIONS                                   					*/
/*																					*/
/************************************************************************************/

// Get waiting queries
$queries = $sql->sql_select_array_query("SELECT * FROM `xmltv_sync` WHERE `active` = 0");
// Loop through queries
foreach ($queries as $quer) {
    // Source ID
    $source_id = $quer['xmltv_source_id'];

    // Make sure the quer we want to update is not already active
    $active = $sql->sql_select_array_query("SELECT active FROM `xmltv_sync` WHERE `xmltv_source_id` = '{$source_id}'");
    if (count($active) >= 1 && $active[0]['active'] == 0) {

        // Set current quer to active
        $sql->sql_query("UPDATE `xmltv_sync` SET `active` = true WHERE `xmltv_source_id` = '{$source_id}'");
        // Get xmltv source
        $source = $sql->sql_select_array_query("SELECT `url`, `format` FROM `xmltv_source` WHERE `id` = '{$source_id}'");
        if (count($source) >= 1) {

            // URL
            $url = $source[0]['url'];
            // Format
            $format = $source[0]['format'];
            // Filename from url
            $filename = basename($url);

            // Download file
            download_file($url, $filename);
            // Extract file and read as XML object
            switch (intval($source[0]['format'])) {
                
                // XML
                case 1: 
                    $xml = extract_xml($filename);
                    break;

                // GZ
                case 2: 
                    $xml = extract_gz($filename);
                    break;

                // XZ
                case 3: 
                    $xml = extract_xz($filename);
                    break;

                // ZIP
                case 4: 
                    $xml = extract_zip($filename);
                    break;

            }

            // Process xml file
            if (isset($xml) && !empty($xml)) {

                // Delete all future programmes for this source
                $d = date('Y-m-d H:i:00');
                $sql->sql_query("DELETE FROM `xmltv_programmes` WHERE xmltv_source_id = '{$source_id}' AND start >= '{$d}'");

                // Loop through channels and insert/update
                foreach ($xml->channel as $channel) {
                    $tvg_id   = $sql->clean_string($channel['id']);
                    $tvg_name = $sql->clean_string($channel->{'display-name'});
                    $language = $sql->clean_string($channel->{'display-name'}['lang']);
                    $sql->sql_query("INSERT INTO xmltv_stations (`xmltv_source_id`, `tvg_id`, `tvg_name`, `lang`) VALUES('{$source_id}', '{$tvg_id}', '{$tvg_name}', '{$language}') ON DUPLICATE KEY UPDATE `xmltv_source_id` = '{$source_id}', `tvg_id` = '{$tvg_id}', `tvg_name` = '{$tvg_name}', `lang` = '{$language}'");
                }

                // Loop through programmes and insert/update
                foreach ($xml->programme as $programme) {
                    $start = xmltv_to_datetime($programme['start']);
                    $stop  = xmltv_to_datetime($programme['stop']);

                    $tvg_id = $sql->clean_string($programme['channel']);
                    
                    $programme_start  = $start->format('Y-m-d H:i:s');
                    $programme_stop   = $stop->format('Y-m-d H:i:s');
                    $programme_offset = $start->getOffset();

                    $programme_title       = $sql->clean_string($programme->title);
                    $programme_subtitle    = $sql->clean_string($programme->{'sub-title'});
                    $programme_description = $sql->clean_string($programme->desc);

                    $series_info = extract_series_info($programme->{'sub-title'});
                    $programme_season  = isset($series_info['season'])  ? $series_info['season']  : -1;
                    $programme_episode = isset($series_info['episode']) ? $series_info['episode'] : -1;

                    $programme_year = $sql->clean_string(extract_year($programme->{'sub-title'}));

                    $sql->sql_query("INSERT INTO xmltv_programmes (`xmltv_source_id`, `tvg_id`, `start`, `stop`, `offset`, `title`, `subtitle`, `description`, `season`, `episode`, `year`) VALUES('{$source_id}', '{$tvg_id}', '{$programme_start}', '{$programme_stop}', '{$programme_offset}', '{$programme_title}', '{$programme_subtitle}', '{$programme_description}', '{$programme_season}', '{$programme_episode}', '{$programme_year}') ON DUPLICATE KEY UPDATE `title` = '{$programme_title}', `subtitle` = '{$programme_subtitle}', `description` = '{$programme_description}', `season` = '{$programme_season}', `episode` = '{$programme_episode}', `year` = '{$programme_year}'");
                }

                // Update source - set synced to now
                $sql->sql_query("UPDATE `xmltv_source` SET `synced_at` = NOW() WHERE id = $source_id");
            }

        }
        // Delete quer from the table - finished!
        $sql->sql_delete("xmltv_sync", [
            "xmltv_source_id" => $source_id
        ]);

    }
}