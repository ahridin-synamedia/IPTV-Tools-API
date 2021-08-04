<?php

/************************************************************************************/
/*																					*/
/*				index.php [ IPTV-Tools Kodi API ]									*/
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
include_once SITE_ROOT . '/common/kodi.php';

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
$kodi   = new kodi();

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

            // Authenticate
            case 'AUTHENTICATE':
                $result = $kodi->authenticate(
                    $p['username'], 
                    $p['password'],
					$p['code']
                );
                $router->json_response($result, $result !== false);
                break;

			// Account Information
			case 'ACCOUNT':
				$router->json_response($kodi->account_information(
					$p['api_key']
				));
				break;

			// Movies
            case 'MOVIES':
				switch ($router->segment(++$segment, true)) {
		
					case 'NOW-IN-THEATERS':
						$router->json_response($kodi->movies_now_in_theaters(
							$p['api_key']
						));
						break;
						
					case 'TOP-RATED':
						$router->json_response($kodi->movies_top_rated(
							$p['api_key']
						));
						break;

					case 'POPULAR':
						$router->json_response($kodi->movies_popular(
							$p['api_key']
						));
						break;

					case 'SEARCH':
						$router->json_response($kodi->movies_search(
							$p['api_key'],
							$p['search']
						));
						break;

					case 'NEW':
						$router->json_response($kodi->movies_new(
							$p['api_key']
						));
						break;

					case 'GROUPS':
						$router->json_response($kodi->movies_groups(
							$p['api_key']
						));
						break;

					case 'BROWSE':
						$router->json_response($kodi->movies_browse_group(
							$p['api_key'],
							$router->segment(++$segment)
						));
						break;

					default:
						$router->json_response('ERROR!!!');
						break;

				}
				break;

			// Series
            case 'SERIES':
				switch ($router->segment(++$segment, true)) {

					case 'NOW-ON-TV':
						$router->json_response($kodi->series_now_on_tv(
							$p['api_key']
						));
						break;

					case 'TOP-RATED':
						$router->json_response($kodi->series_top_rated(
							$p['api_key']
						));
						break;

					case 'POPULAR':
						$router->json_response($kodi->series_popular(
							$p['api_key']
						));
						break;

					case 'SEARCH':
						$router->json_response($kodi->series_search(
							$p['api_key'],
							$p['search']
						));
						break;

					case 'NEW':
						$router->json_response($kodi->series_new(
							$p['api_key']
						));
						break;
				
					case 'GROUPS':
						$router->json_response($kodi->series_groups(
							$p['api_key']
						));
						break;

					case 'BROWSE':
						$router->json_response($kodi->series_browse_group(
							$p['api_key'],
							$router->segment(++$segment)
						));
						break;

					case 'SEASONS':
						$router->json_response($kodi->series_seasons(
							$p['api_key'],
							$p['playlist_id'],
							$p['tmdb_id']
						));
						break;

					case 'EPISODES':
						$router->json_response($kodi->series_episodes(
							$p['api_key'],
							$p['playlist_id'],
							$p['tmdb_id'],
							$p['season']
						));
						break;

					default:
						$router->json_response('ERROR!!!');
						break;

				}
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
