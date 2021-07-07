<?php

/************************************************************************************/
/*																					*/
/*				index.php [ IPTV-Tools XD-Pro API ]									*/
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
include_once SITE_ROOT . '/common/xdpro.php';

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
$router = new routeUrl('/', ROOT_SEGMENT);
$xdpro  = new xdpro();

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
                $result = $xdpro->authenticate(
                    $p['username'], 
                    $p['password']
                );
                $router->json_response($result, $result !== false);
                break;

            // Get download
            case 'DOWNLOAD':
                $result = $xdpro->download($p['api_key']);
                $router->json_response($result, $result !== false);
                break;

            case 'START':
                $result = $xdpro->start(
                    $p['api_key'],
                    $p['id']
                );
                $router->json_response($result, $result !== false);
                break;

            case 'PROGRESS':
                $router->json_response($xdpro->progress(
                    $p['id'],
                    $p['progress']
                ));
                break;

            case 'FINISH':
                $router->json_response($xdpro->finished(
                    $p['id']
                ));
                break;

            case 'ERROR':
                $router->json_response($xdpro->error(
                    $p['id'],
                    $p['error']
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
