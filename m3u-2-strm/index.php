<?php

/************************************************************************************/
/*																					*/
/*				index.php [ IPTV-Tools STRM API ]									*/
/*																					*/
/*				Author	: Ernst Reidinga											*/
/*				Date 	: 05/07/2021												*/
/*				Version	: 1.0														*/
/*																					*/
/************************************************************************************/
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/************************************************************************************/
/*																					*/
/*				Set Timezone to Amsterdam									 		*/
/*																					*/
/************************************************************************************/
date_default_timezone_set('Europe/Amsterdam');

/************************************************************************************/
/*																					*/
/*				Site Root Directory											 		*/
/*																					*/
/************************************************************************************/
define('SITE_ROOT', __DIR__);

/************************************************************************************/
/*																					*/
/*				Include classes												 		*/
/*																					*/
/************************************************************************************/
include_once SITE_ROOT . '/common/const.php';
include_once SITE_ROOT . '/common/router.php';
include_once SITE_ROOT . '/common/db_sql.php';
include_once SITE_ROOT . '/common/m3u2strm.php';

/************************************************************************************/
/*																					*/
/*				Create SQL Connection											 	*/
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
/*				Create Router and XD-Pro classes								 	*/
/*																					*/
/************************************************************************************/
$router   = new routeUrl('/', ROOT_SEGMENT);
$m3u2strm = new m3u2strm();

/************************************************************************************/
/*																					*/
/*				Route API Request		 											*/
/*																					*/
/************************************************************************************/
$segment = 1;
switch ($router->request_method()) {
    
    /************************************/
	/*									*/
	/*			POST Request			*/
	/*									*/
	/************************************/
	case 'POST':
		$p = $router->array_post_data();
		switch ($router->segment($segment, true)) {

            // Authenticate and get list of instances
            case 'AUTHENTICATE':
                $result = $m3u2strm->authenticate(
                    $p['username'], 
                    $p['password']
                );
                $router->json_response($result, $result !== false);
                break;

            // Total Movies
            case 'TOTAL-MOVIES':
                $router->json_response($m3u2strm->total_movies($p['api_key']));
                break;
                
            // Get Movies
            case 'MOVIES':
                $router->json_response($m3u2strm->movies(
                    $p['api_key'],
                    $p['from'],
                    $p['limit']
                ));
                break;

            // Total Series
            case 'TOTAL-SERIES':
                $router->json_response($m3u2strm->total_series($p['api_key']));
                break;
                
            // Get Series
            case 'SERIES':
                $router->json_response($m3u2strm->series(
                    $p['api_key'],
                    $p['from'],
                    $p['limit']
                ));
                break;

            // Get Episodes
            case 'EPISODES':
                $router->json_response($m3u2strm->episodes(
                    $p['api_key'],
                    $p['tmdb_id']
                ));
                break;


            default : $router->invalid_route(); break;

        }
        break;

    /************************************/
	/*									*/
	/*			OTHER Methods			*/
	/*									*/
	/************************************/
	default : $router->invalid_route(); break;

}
