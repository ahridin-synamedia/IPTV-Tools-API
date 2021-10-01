<?php

/************************************************************************************/
/*																					*/
/*				index.php [ IPTV-Tools APP API]										*/
/*																					*/
/*				Author	: Ernst Reidinga											*/
/*				Date 	: 05/08/2021												*/
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
include_once SITE_ROOT . '/common/jwt.php';
include_once SITE_ROOT . '/common/user.php';
include_once SITE_ROOT . '/common/app.php';

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
/*				Create Router and User instance									 	*/
/*																					*/
/************************************************************************************/
$router         = new routeUrl('/', ROOT_SEGMENT);
$user           = new user(SERVER_KEY);
$app            = new app();

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

			case 'LOGIN':
				$router->json_response($user->authenticate(
					$p['username'],
					$p['password'],
					$p['code']
				));
				break;

			case 'LOGOUT':
				$router->json_response($user->logout(
					$user->decode_token($router->getJWT())['payload']->id
				));
				break;

			case 'SEARCH':
				$token = $user->decode_token($router->getJWT());
				if ($token['success'] === false) {
					$router->json_response($token['message'], 401);
					exit();
				} else {
					$token = $token['payload'];
				}
				$router->json_response($app->search(
					$token->id,
					$token->code,
					$p['search'],
					$p['movies'],
					$p['series'],
					$p['catchup'],
					$p['group']
				));
				break;
				
			
			/* Other routes are invalid */
			default: $router->invalid_route(); break;

		}
		break;

	/************************************/
	/*									*/
	/*			 PUT Request			*/
	/*									*/
	/************************************/
	case 'PUT':
		$token = $user->decode_token($router->getJWT());

		// Check if token is valid
		if ($token['success'] === false) {
			$router->json_response($token['message'], 401);
			exit();
		} else {
			$token = $token['payload'];
		}

		$p = $router->array_post_data();
		switch ($router->segment($segment, true)) {

			case 'XDPRO':
				switch ($router->segment(++$segment, true)) {

					case 'UPDATE':
						$router->json_response($app->update_xdpro_download(
							$token->id,
							$p['id'],
							$p
						));
						break;

					case 'RESUME':
						$router->json_response($app->update_xdpro_download(
							$token->id,
							$p['id'],
							['enabled' => 1]
						));
						break;

					case 'PAUSE':
						$router->json_response($app->update_xdpro_download(
							$token->id,
							$p['id'],
							['enabled' => 0]
						));
						break;

					case 'DELETE':
						$router->json_response($app->delete_xdpro_download(
							$token->id,
							$p['id']
						));
						break;

					case 'RESUME-ALL':
						$router->json_response($app->resume_xdpro_downloads(
							$token->id
						));
						break;

					case 'PAUSE-ALL':
						$router->json_response($app->pause_xdpro_downloads(
							$token->id
						));
						break;

					case 'DELETE-ALL':
						$router->json_response($app->delete_xdpro_downloads(
							$token->id
						));
						break;

					case 'DELETE-DISABLED':
						$router->json_response($app->delete_xdpro_downloads(
							$token->id,
							true
						));
						break;

				}
				break;
				
			
			/* Other routes are invalid */
			default: $router->invalid_route(); break;

		}
		break;

	/************************************/
	/*									*/
	/*			GET Request 			*/
	/*									*/
	/************************************/
	case 'GET':
		$token = $user->decode_token($router->getJWT());

		// Check if token is valid
		if ($token['success'] === false) {
			$router->json_response($token['message'], 401);
			exit();
		} else {
			$token = $token['payload'];
		}
		
		switch ($router->segment($segment, true)) {

			case 'TOTALS':
				$router->json_response($app->totals(
					$token->id,
					$token->code
				));
				break;

			case 'XDPRO':
				switch ($router->segment(++$segment, true)) {

					case 'INSTANCES':
						$router->json_response($app->xdpro_instances(
							$token->id
						));
						break;

					case 'DOWNLOADS':
						$router->json_response($app->xdpro_downloads(
							$token->id
						));
						break;

				}
				break;

		}
		break;

	/************************************/
	/*									*/
	/*			OPTIONS					*/
	/*									*/
	/************************************/
	case 'OPTIONS': 
		header('Access-Control-Max-Age: 600');
		http_response_code(200); 
		break;

	/************************************/
	/*									*/
	/*			OTHER Methods			*/
	/*									*/
	/************************************/
	default : $router->invalid_route(); break;

}