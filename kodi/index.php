<?php

/************************************************************************************/
/*																					*/
/*				index.php [ Kodi Addons Index Page ]								*/
/*																					*/
/*				Author	: Ernst Reidinga 											*/
/*				Date 	: 24/07/2021 14:00											*/
/*				Version	: 1.0														*/
/*																					*/
/************************************************************************************/


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/************************************************************************************/
/*																					*/
/*				Site Root Directory											 		*/
/*																					*/
/************************************************************************************/
define('SITE_ROOT', __DIR__);

/************************************************************************************/
/*																					*/
/*				Include classes														*/
/*																					*/
/************************************************************************************/
include_once SITE_ROOT . '/common/const.php';
include_once SITE_ROOT . '/common/db_sql.php';

/************************************************************************************/
/*																					*/
/*				Create SQL Connections											 	*/
/*																					*/
/************************************************************************************/
$sql = new dbSQL(
	SQL['app']['server'],
	SQL['app']['username'],
	SQL['app']['password'],
	SQL['app']['dbname']
);

/************************************************************************************/
/*																					*/
/*				Select Addons													 	*/
/*																					*/
/************************************************************************************/
$addons = $sql->sql_select_array('kodi_addons', ['*']);

/************************************************************************************/
/*																					*/
/*				Format Bytes to B/KB/MB/GB/TB									 	*/
/*																					*/
/************************************************************************************/
function formatBytes ($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('', 'K', 'M', 'G', 'T');   
    return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
} 

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<html>

<head>
    <title>IPTV-Tools Kodi Addons</title>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <meta property=og:url content=https://iptv-tools.com>
    <meta property=og:type content=website>
    <meta property=og:title content="IPTV-Tools">
    <meta property=og:description content="IPTV-Tools Kodi Addons">
    <meta property=og:image content=https://iptv-tools.com/images/iptv-tools.png>
    <meta property=og:site_name content="IPTV-Tools">
    <meta name=twitter:image:alt content="IPTV-Tools">
    <meta name=twitter:card content=summary_large_image>
    <meta name=keywords content="iptv-tools, iptv-tools.com, erdesigns.eu, erdesigns software, kodi, addons">
    <meta name=description content="IPTV-Tools Kodi Addons">
    <meta name=robots content=index,follow>
    <meta http-equiv="Pragma" content="no-cache">
</head>

<body>
    <div class="text-center">
    	<img src="https://IPTV-Tools.com/images/iptv-tools.png" style="max-height: 64px; max-width: 64px; margin-top: 3em;">
    	<h3>IPTV-Tools Kodi Addons</h3>
    </div>
    <div style="padding: 3em;">
    <table class="table table-striped">
        <thead>
        <tr>
            <th>Name</th>
            <th>Last modified</th>
            <th>Size</th>
            <th>Description</th>
        </tr>
    	</thead>
        <?php foreach ($addons as $addon): ?>
        <?php if(intval($addon['enabled']) === 1 && file_exists($addon['filename'])): ?>
        <tr>
            <td><a href="<?php echo $addon['filename']; ?>"><?php echo $addon['filename']; ?></a></td>
            <td><?php echo date('d/m/Y H:i:s', strtotime($addon['last_modified']) ); ?></td>
            <td><?php echo formatBytes(filesize($addon['filename'])); ?></td>
            <td><?php echo $addon['description']; ?></td>
        </tr>
    	<?php endif; ?>
    	<?php endforeach; ?>
    </table>
	</div>
</body>

</html>