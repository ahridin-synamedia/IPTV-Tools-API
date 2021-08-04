<?php

/************************************************************************************/
/*																					*/
/*				index.php [ IPTV-Tools API]											*/
/*																					*/
/*				Author	: Ernst Reidinga											*/
/*				Date 	: 12/04/2021												*/
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
include_once SITE_ROOT . '/common/administration.php';
include_once SITE_ROOT . '/common/toolbox.php';
include_once SITE_ROOT . '/common/playlist.php';
include_once SITE_ROOT . '/common/group.php';
include_once SITE_ROOT . '/common/live.php';
include_once SITE_ROOT . '/common/movie.php';
include_once SITE_ROOT . '/common/serie.php';
include_once SITE_ROOT . '/common/editor.php';
include_once SITE_ROOT . '/common/xdpro.php';
include_once SITE_ROOT . '/common/siptv.php';
include_once SITE_ROOT . '/common/m3u2strm.php';
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
/*				Create Router and User instance									 	*/
/*																					*/
/************************************************************************************/
$router         = new routeUrl('/', ROOT_SEGMENT);
$user           = new user(SERVER_KEY);
$administration = new administration();
$toolbox        = new toolbox();
$playlist       = new playlist();
$group		    = new group();
$live		    = new live();
$movie		    = new movie();
$serie		    = new serie();
$editor		    = new editor();
$xdpro		    = new xdpro();
$siptv          = new siptv();
$m3u2strm       = new m3u2strm();
$kodi           = new kodi();

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

			/************************************/
			/*									*/
			/*			 	USERS				*/
			/*									*/
			/************************************/
			case 'ADMINISTRATION':
				$token = $user->decode_token($router->getJWT());

				// Check if token is valid and if user has clearance to request this info
				if ($token['success'] === false || !in_array($token['payload']->role, [1, 2, 1985])) {
					$router->json_response($token['message'], 401);
					exit();
				}
				switch ($router->segment(++$segment, true)) {

					case 'DASHBOARD':
						switch ($router->segment(++$segment, true)) {

							case 'SERVER':
								$router->json_response($administration->dashboard_server());
								break;

							case 'DATABASE':
								$router->json_response($administration->dashboard_database());
								break;

						}
						break;
					
					case 'USER':
						switch ($router->segment(++$segment, true)) {

							case 'INSERT':
								$router->json_response($administration->insert_user($p));
								break;

							case 'UPDATE':
								$router->json_response($administration->update_user($p['user'], $p['id']));
								break;
							
							case 'DELETE':
								$router->json_response($administration->delete_user($p['id']));
								break;

						}
						break;

					case 'PROFILE':
						switch ($router->segment(++$segment, true)) {

							case 'INSERT':
								$router->json_response($administration->insert_profile($p));
								break;

							case 'UPDATE':
								$router->json_response($administration->update_profile($p['profile'], $p['id']));
								break;
							
							case 'DELETE':
								$router->json_response($administration->delete_profile($p['id']));
								break;

						}
						break;

					case 'SUBSCRIPTION':
						switch ($router->segment(++$segment, true)) {

							case 'INSERT':
								$router->json_response($administration->insert_subscription($p));
								break;

							case 'UPDATE':
								$router->json_response($administration->update_subscription($p['subscription'], $p['id']));
								break;
							
							case 'DELETE':
								$router->json_response($administration->delete_subscription($p['id']));
								break;

						}
						break;

					case 'LOGIN':
						switch ($router->segment(++$segment, true)) {

							case 'DELETE':
								$router->json_response($administration->delete_login($p['id']));
								break;

						}
						break;

					case 'CONFIRM':
						switch ($router->segment(++$segment, true)) {

							case 'DELETE':
								$router->json_response($administration->delete_confirm($p['id']));
								break;

						}
						break;

					case 'PAYMENT':
						switch ($router->segment(++$segment, true)) {

							case 'DELETE':
								$router->json_response($administration->delete_payment($p['id']));
								break;

						}
						break;

					case 'INVOICE':
						switch ($router->segment(++$segment, true)) {

							case 'DELETE':
								$router->json_response($administration->delete_invoice($p['id']));
								break;

						}
						break;

					case 'TICKET':
						switch ($router->segment(++$segment, true)) {

							case 'INSERT':
								$router->json_response($administration->insert_ticket($p));
								break;

							case 'UPDATE':
								$router->json_response($administration->update_ticket($p['ticket'], $p['id']));
								break;
							
							case 'CLOSE':
								$router->json_response($administration->close_ticket($p['id']));
								break;

							case 'DELETE':
								$router->json_response($administration->delete_ticket($p['id']));
								break;

						}
						break;

					case 'XMLTV':
						switch ($router->segment(++$segment, true)) {

							case 'INSERT':
								$router->json_response($administration->insert_xmltv($p));
								break;

							case 'UPDATE':
								$router->json_response($administration->update_xmltv($p['xmltv'], $p['id']));
								break;

							case 'DELETE':
								$router->json_response($administration->delete_xmltv($p['id']));
								break;

							case 'SYNC':
								$router->json_response($administration->sync_xmltv($p['id']));
								break;

						}
						break;

					case 'SCHEDULER':
						switch ($router->segment(++$segment, true)) {

							case 'INSERT':
								$router->json_response($administration->insert_schedule($p));
								break;

							case 'UPDATE':
								$router->json_response($administration->update_schedule($p['schedule'], $p['id']));
								break;

							case 'DELETE':
								$router->json_response($administration->delete_schedule($p['id']));
								break;

							case 'EXECUTE':
								$router->json_response($administration->execute_schedule($p['id']));
								break;

						}
						break;

					/* Other routes are invalid */
					default: $router->invalid_route(); break;

				}
				break;

			/************************************/
			/*									*/
			/*			 	USERS				*/
			/*									*/
			/************************************/
			case 'USERS':
				switch ($router->segment(++$segment, true)) {

					case 'AUTHENTICATE':
						$router->json_response($user->authenticate(
							$p['username'],
							$p['password']
						));
						break;

					case 'UPDATE':
						$router->json_response($user->authenticate(
							$p['username'],
							$p['password']
						));
						break;

					case 'UPDATE-PASSWORD':
						$router->json_response($user->update_password(
							$p['username'],
							$p['password'],
							$p['new_password']
						));
						break;

					case 'LOGOUT':
						$router->json_response($user->logout(
							$user->decode_token($router->getJWT())['payload']->id
						));
						break;

					case 'RESET':
						$router->json_response($user->reset(
							$p['username'],
							$p['email']
						));
						break;

					case 'REGISTER':
						$router->json_response($user->register(
							$p['username'],
							$p['password'],
							$p['email']
						));
						break;

					case 'CONFIRM':
						$router->json_response($user->confirm(
							$p['code']
						));
						break;

					case 'PROFILE':
						$router->json_response($user->register_profile($p));
						break;

					case 'AVAILABLE':
						$router->json_response([
							'available' =>$user->available(
								$p['username']
							)
						]);
						break;

					case 'TICKETS':
						$router->json_response($user->ticket($p));
						break;

					case 'PAYMENT':
						$user->create_payment($p['payment']);
						$user->create_invoice($p['invoice']);
						$router->json_response($user->register_subscription(
							$p['user_id'],
							$p['subscription']
						));
						break;

					case 'CANCEL':
						$router->json_response($user->cancel_user(
							$p['user_id'],
							$p['password']
						));
						break;

					/* Other routes are invalid */
					default: $router->invalid_route(); break;

				}
				break;

			/************************************/
			/*									*/
			/*			   PLAYLIST	    		*/
			/*									*/
			/************************************/
			case 'PLAYLIST':
				$token = $user->decode_token($router->getJWT());

				// Check if token is valid
				if ($token['success'] === false) {
					$router->json_response($token['message'], 401);
					exit();
				} else {
					$token = $token['payload'];
				}
				switch ($router->segment(++$segment, true)) {

					case 'ADD':
						$router->json_response($playlist->add(
							$token->id
						));
						break;

					case 'AUTHENTICATE':
						$router->json_response($toolbox->playlist_auth(
							$p['host'],
							$p['port'],
							$p['username'],
							$p['password']
						));
						break;

					case 'LOAD-GROUPS':
						$router->json_response($toolbox->playlist_load_groups(
							$p['host'],
							$p['port'],
							$p['username'],
							$p['password']
						));
						break;

					case 'USER-PASS-EXISTS':
						$router->json_response($toolbox->playlist_user_pass_exists(
							$p['username'],
							$p['password']
						));
						break;

					case 'RANDOM-PASSWORD':
						$router->json_response($toolbox->playlist_random_password(
							$p['username']
						));
						break;

					case 'SYNCHRONIZE':
						$router->json_response($playlist->synchronize(
							$token->id,
							$p['id']
						));
						break;

					case 'SYNCHRONIZE-TMDB':
						$router->json_response($playlist->synchronize_tmdb(
							$token->id,
							$p['id']
						));
						break;

					case 'RESTORE':
						switch ($router->segment(++$segment, true)) {

							case 'PLAYLIST':
								$router->json_response($toolbox->restore_playlist( 
									$token->id,
									$router->segment(++$segment, true),
									$_FILES['backup_zip']['tmp_name']
								));
								break;

							case 'EPG-CODES':
								$router->json_response($toolbox->restore_epgcodes(
									$token->id,
									$router->segment(++$segment, true),
									$_FILES['backup_zip']['tmp_name']
								));
								break;

						}
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			   GROUP	    		*/
			/*									*/
			/************************************/
			case 'GROUP':
				$token = $user->decode_token($router->getJWT());

				// Check if token is valid
				if ($token['success'] === false) {
					$router->json_response($token['message'], 401);
					exit();
				} else {
					$token = $token['payload'];
				}
				switch ($router->segment(++$segment, true)) {

					case 'ADD':
						$router->json_response($group->add(
							$token->id
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			   	LIVE	    		*/
			/*									*/
			/************************************/
			case 'LIVE':
				$token = $user->decode_token($router->getJWT());

				// Check if token is valid
				if ($token['success'] === false) {
					$router->json_response($token['message'], 401);
					exit();
				} else {
					$token = $token['payload'];
				}
				switch ($router->segment(++$segment, true)) {

					case 'ADD':
						$router->json_response($live->add(
							$token->id
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			   	MOVIE	    		*/
			/*									*/
			/************************************/
			case 'MOVIE':
				$token = $user->decode_token($router->getJWT());

				// Check if token is valid
				if ($token['success'] === false) {
					$router->json_response($token['message'], 401);
					exit();
				} else {
					$token = $token['payload'];
				}
				switch ($router->segment(++$segment, true)) {

					case 'ADD':
						$router->json_response($movie->add(
							$token->id
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			   	SERIES	    		*/
			/*									*/
			/************************************/
			case 'SERIES':
				$token = $user->decode_token($router->getJWT());

				// Check if token is valid
				if ($token['success'] === false) {
					$router->json_response($token['message'], 401);
					exit();
				} else {
					$token = $token['payload'];
				}
				switch ($router->segment(++$segment, true)) {

					case 'ADD':
						$router->json_response($serie->add(
							$token->id
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			   	EDITOR	    		*/
			/*									*/
			/************************************/
			case 'EDITOR':
				$token = $user->decode_token($router->getJWT());

				// Check if token is valid
				if ($token['success'] === false) {
					$router->json_response($token['message'], 401);
					exit();
				} else {
					$token = $token['payload'];
				}
				switch ($router->segment(++$segment, true)) {

					case 'IMPORT':
						$router->json_response($editor->import(
							$token->id,
							$p['playlist_id'],
							$p['import']
						));
						break;

					case 'ADD-GROUP':
						$router->json_response($editor->add_group(
							$token->id,
							$p['playlist_id']
						));
						break;

					case 'ADD-STREAM':
						$router->json_response($editor->add_stream(
							$token->id,
							$p['playlist_id'],
							$p['group_id'],
							$p['stream_type']
						));
						break;

					case 'ADD-STREAMS':
						$router->json_response($editor->add_streams(
							$token->id,
							$p['playlist_id'],
							$p['group_id'],
							$p['streams'],
							$router->segment(++$segment)
						));
						break;

					case 'MOVE-TO-GROUP':
						$router->json_response($editor->move_streams_to_group(
							$token->id,
							$p['playlist_id'],
							$p['streams'],
							$p['group_id'],
							$router->segment(++$segment)
						));
						break;

					case 'UPDATE-GROUPS':
						$router->json_response($editor->update_groups(
							$token->id,
							$p['playlist_id'],
							$p['groups']
						));
						break;

					case 'DELETE-GROUPS':
						$router->json_response($editor->delete_groups(
							$token->id,
							$p['playlist_id'],
							$p['groups']
						));
						break;
						
					case 'UPDATE-STREAMS':
						$router->json_response($editor->update_streams(
							$token->id,
							$p['playlist_id'],
							$p['streams'],
							$router->segment(++$segment)
						));
						break;

					case 'DELETE-STREAMS':
						$router->json_response($editor->delete_streams(
							$token->id,
							$p['playlist_id'],
							$p['streams'],
							$router->segment(++$segment)
						));
						break;

					case 'FIND-RADIOBROWSER':
						$router->json_response($toolbox->find_on_radiobrowser(
							$p['search']
						));
						break;

					case 'SOUNDCLOUD-TRACK':
						$router->json_response($toolbox->soundcloud(
							$p['url'],
							$p['client_id']
						));
						break;
						
					case 'YOUTUBE-VIDEO':
						$router->json_response($toolbox->youtube_page(
							$p['video_id']
						));
						break;

					case 'VIMEO-VIDEO':
						$router->json_response($toolbox->vimeo_config(
							$p['video_id']
						));
						break;

					case 'DAILYMOTION-VIDEO':
						$router->json_response($toolbox->dailymotion_config(
							$p['video_id']
						));
						break;

					case 'TED-TALKS-VIDEO':
						$router->json_response($toolbox->tedtalks_config(
							$p['url']
						));
						break;

					case 'XHAMSTER-VIDEO':
						$router->json_response($toolbox->xhamster_config(
							$p['url']
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			   	XD-PRO	    		*/
			/*									*/
			/************************************/
			case 'XD-PRO':
				$token = $user->decode_token($router->getJWT());

				// Check if token is valid
				if ($token['success'] === false) {
					$router->json_response($token['message'], 401);
					exit();
				} else {
					$token = $token['payload'];
				}
				switch ($router->segment(++$segment, true)) {

					case 'INSTANCE':
						$router->json_response($xdpro->add_instance(
							$token->id
						));
						break;

					case 'DOWNLOAD':
						$router->json_response($xdpro->add_download(
							$token->id,
							$p
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*		   	  M3U-2-STRM    		*/
			/*									*/
			/************************************/
			case 'M3U-2-STRM':
				$token = $user->decode_token($router->getJWT());

				// Check if token is valid
				if ($token['success'] === false) {
					$router->json_response($token['message'], 401);
					exit();
				} else {
					$token = $token['payload'];
				}
				switch ($router->segment(++$segment, true)) {

					case 'INSTANCE':
						$router->json_response($m3u2strm->add_instance(
							$token->id
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*		   	  	 KODI	    		*/
			/*									*/
			/************************************/
			case 'KODI':
				$token = $user->decode_token($router->getJWT());

				// Check if token is valid
				if ($token['success'] === false) {
					$router->json_response($token['message'], 401);
					exit();
				} else {
					$token = $token['payload'];
				}
				switch ($router->segment(++$segment, true)) {

					case 'INSTANCE':
						$router->json_response($kodi->add_instance(
							$token->id
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			   	SIPTV	    		*/
			/*									*/
			/************************************/
			case 'SIPTV':
				$token = $user->decode_token($router->getJWT());

				// Check if token is valid
				if ($token['success'] === false) {
					$router->json_response($token['message'], 401);
					exit();
				} else {
					$token = $token['payload'];
				}
				switch ($router->segment(++$segment, true)) {

					case 'PROFILE':
						$router->json_response($siptv->add_profile(
							$token->id
						));
						break;

					case 'DELETE-PLAYLIST':
						$router->json_response($siptv->delete_playlist(
							urlencode($p['mac'])
						));
						break;

					case 'UPLOAD-PLAYLIST':
						$router->json_response($siptv->upload_playlist(
							$token->id,
							$p
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*		  	   MAC-2-M3U     		*/
			/*									*/
			/************************************/
			case 'MAC-2-M3U':
				$router->json_response($toolbox->convert_mac_to_m3u(
					$p['mac'],
					$p['portal']
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

			/************************************/
			/*									*/
			/*			   PLAYLIST	    		*/
			/*									*/
			/************************************/
			case 'PLAYLIST':
				$router->json_response($playlist->update(
					$token->id,
					$router->segment(++$segment),
					$p
				));
				break;

			/************************************/
			/*									*/
			/*			    GROUP	    		*/
			/*									*/
			/************************************/
			case 'GROUP':
				$router->json_response($group->update(
					$token->id,
					$router->segment(++$segment),
					$p
				));
				break;

			/************************************/
			/*									*/
			/*			    LIVE	    		*/
			/*									*/
			/************************************/
			case 'LIVE':
				$router->json_response($live->update(
					$token->id,
					$router->segment(++$segment),
					$p
				));
				break;

			/************************************/
			/*									*/
			/*			    MOVIE	    		*/
			/*									*/
			/************************************/
			case 'MOVIE':
				$router->json_response($movie->update(
					$token->id,
					$router->segment(++$segment),
					$p
				));
				break;

			/************************************/
			/*									*/
			/*			    SERIES	    		*/
			/*									*/
			/************************************/
			case 'SERIES':
				$router->json_response($serie->update(
					$token->id,
					$router->segment(++$segment),
					$p
				));
				break;

			/************************************/
			/*									*/
			/*			    XD-PRO	    		*/
			/*									*/
			/************************************/
			case 'XD-PRO':
				switch ($router->segment(++$segment, true)) {

					case 'INSTANCE':
						$router->json_response($xdpro->update_instance(
							$token->id,
							$router->segment(++$segment),
							$p
						));
						break;

					case 'DOWNLOAD':
						$router->json_response($xdpro->update_download(
							$token->id,
							$router->segment(++$segment),
							$p
						));
						break;

					case 'PAUSE':
						$router->json_response($xdpro->pause_downloads(
							$token->id
						));
						break;

					case 'RESUME':
						$router->json_response($xdpro->resume_downloads(
							$token->id
						));
						break;

					case 'DELETE':
						$router->json_response($xdpro->delete_downloads(
							$token->id,
							intval($router->segment(++$segment)) === 1
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			  M3U-2-STRM    		*/
			/*									*/
			/************************************/
			case 'M3U-2-STRM':
				switch ($router->segment(++$segment, true)) {

					case 'INSTANCE':
						$router->json_response($m3u2strm->update_instance(
							$token->id,
							$router->segment(++$segment),
							$p
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			  	 KODI	    		*/
			/*									*/
			/************************************/
			case 'KODI':
				switch ($router->segment(++$segment, true)) {

					case 'INSTANCE':
						$router->json_response($kodi->update_instance(
							$token->id,
							$router->segment(++$segment),
							$p
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			    SIPTV	    		*/
			/*									*/
			/************************************/
			case 'SIPTV':
				switch ($router->segment(++$segment, true)) {

					case 'PROFILE':
						$router->json_response($siptv->update_profile(
							$token->id,
							$router->segment(++$segment),
							$p
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
	/*			GET Request				*/
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

			/************************************/
			/*									*/
			/*			 	USERS				*/
			/*									*/
			/************************************/
			case 'ADMINISTRATION':
				if (!in_array($token->role, [1, 2, 1985])) {
					$router->json_response($token['message'], 401);
					exit();
				}
				switch ($router->segment(++$segment, true)) {

					case 'USERS':
						$router->json_response($administration->users());
						break;

					case 'PROFILES':
						$router->json_response($administration->profiles());
						break;

					case 'PROFILE':
						$router->json_response($administration->profile($router->segment(++$segment)));
						break;

					case 'SUBSCRIPTIONS':
						$router->json_response($administration->subscriptions());
						break;

					case 'LOGINS':
						$router->json_response($administration->logins());
						break;

					case 'CONFIRMS':
						$router->json_response($administration->confirms());
						break;

					case 'PAYMENTS':
						$router->json_response($administration->payments());
						break;

					case 'INVOICES':
						$router->json_response($administration->invoices());
						break;

					case 'FINANCIAL-STATISTICS':
						$router->json_response($administration->financial_statistics());
						break;

					case 'TICKETS':
						$router->json_response($administration->tickets(
							$router->segment(++$segment)
						));
						break;

					case 'XMLTV':
						$router->json_response($administration->xmltv());
						break;

					case 'XMLTV-SYNC':
						$router->json_response($administration->get_waiting_xmltv());
						break;

					case 'SCHEDULER':
						$router->json_response($administration->schedules());
						break;

					/* Other routes are invalid */
					default: $router->invalid_route(); break;

				}
				break;
			
			/************************************/
			/*									*/
			/*			 	USERS				*/
			/*									*/
			/************************************/
			case 'USERS':
				switch ($router->segment(++$segment, true)) {

					case 'PROFILE':
						$router->json_response($user->profile(
							$token->id
						));
						break;

					case 'INVOICES':
						$router->json_response($user->invoices(
							$token->id
						));
						break;

					case 'SUBSCRIPTIONS':
						$router->json_response($user->subscriptions(
							$token->id
						));

					case 'TICKETS':
						$router->json_response($user->tickets(
							$token->id
						));

				}
				break;

			/************************************/
			/*									*/
			/*			   PLAYLIST	    		*/
			/*									*/
			/************************************/
			case 'PLAYLIST':
				switch ($router->segment(++$segment, true)) {

					case 'EXPORT':
						switch ($router->segment(++$segment, true)) {

							case 'M3U':
								$toolbox->export_to_m3u(
									$token->id,
									$router->segment(++$segment, true)
								);
								break;

							case 'SIPTV':
								$toolbox->export_to_siptv(
									$token->id,
									$router->segment(++$segment, true)
								);
								break;

							case 'BOUQUET':
								$toolbox->export_to_bouquet(
									$token->id,
									$router->segment(++$segment, true)
								);
								break;

							case 'CSV':
								$toolbox->export_to_csv(
									$token->id,
									$router->segment(++$segment, true)
								);
								break;

							case 'JSON':
								$toolbox->export_to_json(
									$token->id,
									$router->segment(++$segment, true)
								);
								break;

						}
						break;

					case 'BACK-UP':
						switch ($router->segment(++$segment, true)) {

							case 'PLAYLIST':
								$toolbox->backup_playlist(
									$token->id,
									$router->segment(++$segment, true),
									true, // Live
									true, // Movies
									true  // Series
								);
								break;

							case 'LIVE':
								$toolbox->backup_playlist( 
									$token->id,
									$router->segment(++$segment, true),
									true // Live
								);
								break;

							case 'MOVIES':
								$toolbox->backup_playlist( 
									$token->id,
									$router->segment(++$segment, true),
									false, // Live
									true   // Movies
								);
								break;

							case 'SERIES':
								$toolbox->backup_playlist( 
									$token->id,
									$router->segment(++$segment, true),
									false, // Live
									false, // Movies
									true   // Series
								);
								break;

							case 'EPG-CODES':
								$toolbox->backup_epgcodes(
									$token->id,
									$router->segment(++$segment, true)
								);
								break;

						}
						break;

					case 'IP-ADDRESS':
						$router->json_response($router->ip_address());
						break;

					case 'SYNCHRONIZE':
						$router->json_response($playlist->synchronize_active(
							$token->id
						));
						break;

					case 'SYNCHRONIZE-TMDB':
						$router->json_response($playlist->synchronize_tmdb_active(
							$token->id
						));
						break;

					case 'SIMPLE':
						$router->json_response($playlist->get(
							$token->id,
							true
						));
						break;

					default:
						$router->json_response($playlist->get(
							$token->id
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			    GROUP	    		*/
			/*									*/
			/************************************/
			case 'GROUP':
				switch ($router->segment(++$segment, true)) {

					case 'TOTAL':
						$router->json_response($group->total(
							$token->id
						));
						break;

					case 'SIMPLE':
						$router->json_response($group->get(
							$token->id,
							0,
							0,
							true
						));
						break;

					case 'CATCH-UP':
						$router->json_response($group->get_catchup(
							$token->id,
							$router->segment(++$segment)
						));
						break;

					default:
						$router->json_response($group->get(
							$token->id,
							$router->segment($segment),
							$router->segment(++$segment)
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			    LIVE	    		*/
			/*									*/
			/************************************/
			case 'LIVE':
				switch ($router->segment(++$segment, true)) {

					case 'TOTAL':
						$router->json_response($live->total(
							$token->id
						));
						break;

					case 'SIMPLE':
						$router->json_response($live->get(
							$token->id,
							0,
							0,
							true
						));
						break;

					case 'CATCH-UP':
						$router->json_response($live->get_catchup(
							$token->id,
							$router->segment(++$segment),
							$router->segment(++$segment),
							$router->segment(++$segment)
						));
						break;

					default:
						$router->json_response($live->get(
							$token->id,
							$router->segment($segment),
							$router->segment(++$segment)
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			    MOVIE	    		*/
			/*									*/
			/************************************/
			case 'MOVIE':
				switch ($router->segment(++$segment, true)) {

					case 'TOTAL':
						$router->json_response($movie->total(
							$token->id
						));
						break;

					case 'SIMPLE':
						$router->json_response($movie->get(
							$token->id,
							0,
							0,
							true
						));
						break;

					default:
						$router->json_response($movie->get(
							$token->id,
							$router->segment($segment),
							$router->segment(++$segment)
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			    MOVIES	    		*/
			/*									*/
			/************************************/
			case 'MOVIES':
				switch ($router->segment(++$segment, true)) {

					case 'NOW-PLAYING':
						$router->json_response($movie->now_playing(
							$token->id,
							$router->segment(++$segment),
							$router->segment(++$segment),
							$router->segment(++$segment)
						));
						break;

					case 'TOP-RATED':
						$router->json_response($movie->top_rated(
							$token->id,
							$router->segment(++$segment),
							$router->segment(++$segment),
							$router->segment(++$segment)
						));
						break;

					case 'POPULAR':
						$router->json_response($movie->popular(
							$token->id,
							$router->segment(++$segment),
							$router->segment(++$segment),
							$router->segment(++$segment)
						));
						break;

					case 'TOTAL':
						$router->json_response($movie->browse_total(
							$token->id,
							$router->segment(++$segment),
						));
						break;

					case 'BROWSE':
						$router->json_response($movie->browse(
							$token->id,
							$router->segment(++$segment),
							$router->segment(++$segment),
							$router->segment(++$segment)
						));
						break;

					case 'SEARCH':
						$router->json_response($movie->search(
							$token->id,
							$router->segment(++$segment),
							$router->segment(++$segment)
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			    SERIES	    		*/
			/*									*/
			/************************************/
			case 'SERIES':
				switch ($router->segment(++$segment, true)) {

					case 'TOTAL':
						$router->json_response($serie->total(
							$token->id
						));
						break;

					case 'SIMPLE':
						$router->json_response($serie->get(
							$token->id,
							0,
							0,
							true
						));
						break;

					default:
						$router->json_response($serie->get(
							$token->id,
							$router->segment($segment),
							$router->segment(++$segment)
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			    SERIE	    		*/
			/*									*/
			/************************************/
			case 'SERIE':
				switch ($router->segment(++$segment, true)) {

					case 'ON-THE-AIR':
						$router->json_response($serie->now_on_tv(
							$token->id,
							$router->segment(++$segment),
							$router->segment(++$segment),
							$router->segment(++$segment)
						));
						break;

					case 'POPULAR':
						$router->json_response($serie->popular(
							$token->id,
							$router->segment(++$segment),
							$router->segment(++$segment),
							$router->segment(++$segment)
						));
						break;

					case 'TOP-RATED':
						$router->json_response($serie->top_rated(
							$token->id,
							$router->segment(++$segment),
							$router->segment(++$segment),
							$router->segment(++$segment)
						));
						break;

					case 'BROWSE':
						$router->json_response($serie->browse(
							$token->id,
							$router->segment(++$segment),
							$router->segment(++$segment),
							$router->segment(++$segment)
						));
						break;
					
					case 'TOTAL':
						$router->json_response($serie->browse_total(
							$token->id,
							$router->segment(++$segment)
						));
						break;

					case 'SEARCH':
						$router->json_response($serie->search(
							$token->id,
							$router->segment(++$segment),
							$router->segment(++$segment)
						));
						break;	
					
					case 'DETAILS':
						$router->json_response($serie->details(
							$token->id,
							$router->segment(++$segment),
							$router->segment(++$segment)
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			    EDITOR	    		*/
			/*									*/
			/************************************/
			case 'EDITOR':
				switch ($router->segment(++$segment, true)) {

					case 'SOUNDCLOUD-CLIENT-ID':
						$router->json_response($toolbox->soundcloud_clientid());
						break;

					case 'GROUPS':
						$router->json_response($editor->groups(
							$token->id,
							$router->segment(++$segment)
						));
						break;

					case 'STREAMS':
						$router->json_response($editor->streams(
							$token->id,
							$router->segment(++$segment),
							$router->segment(++$segment),
							$router->segment(++$segment)
						));
						break;

					case 'LOGO-COUNTRIES':
						$router->json_response($toolbox->logos_countries());
						break;

					case 'LOGOS':
						$router->json_response($toolbox->logos(
							$router->segment(++$segment)
						));
						break;

					case 'TV-GUIDE-COUNTRIES':
						$router->json_response($toolbox->tvguide_countries());
						break;

					case 'TV-GUIDE':
						$router->json_response($toolbox->tvguide_ids(
							$router->segment(++$segment)
						));
						break;

					case 'SMART-IPTV-COUNTRIES':
						$router->json_response($toolbox->siptv_countries());
						break;

					case 'SMART-IPTV':
						$router->json_response($toolbox->siptv_epg_codes(
							$router->segment(++$segment)
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			    DASHBOARD	 		*/
			/*									*/
			/************************************/
			case 'DASHBOARD':
				switch ($router->segment(++$segment, true)) {

					case 'STATISTICS':
						$router->json_response($toolbox->user_statistics(
							$token->id
						));
						break;

					case 'XTREAM-ACCOUNTS':
						$router->json_response($toolbox->user_xtreamaccounts(
							$token->id
						));
						break;

					case 'PLAYLISTS':
						$router->json_response($toolbox->user_playlists(
							$token->id
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			    XD-PRO		 		*/
			/*									*/
			/************************************/
			case 'XD-PRO':
				switch ($router->segment(++$segment, true)) {

					case 'INSTANCES':
						$router->json_response($xdpro->get_instances(
							$token->id
						));
						break;

					case 'DOWNLOAD':
						$router->json_response($xdpro->get_downloads(
							$token->id
						));
						break;

					case 'USERAGENTS':
						$router->json_response($xdpro->get_useragents());
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			  M3U-2-STRM	 		*/
			/*									*/
			/************************************/
			case 'M3U-2-STRM':
				switch ($router->segment(++$segment, true)) {

					case 'INSTANCES':
						$router->json_response($m3u2strm->get_instances(
							$token->id
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			   	 KODI		 		*/
			/*									*/
			/************************************/
			case 'KODI':
				switch ($router->segment(++$segment, true)) {

					case 'INSTANCES':
						$router->json_response($kodi->get_instances(
							$token->id
						));
						break;

					case 'GROUPS':
						$router->json_response($kodi->get_groups(
							$token->id
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			    SIPTV		 		*/
			/*									*/
			/************************************/
			case 'SIPTV':
				switch ($router->segment(++$segment, true)) {

					case 'PROFILES':
						$router->json_response($siptv->get_profiles(
							$token->id
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			    TMDB		 		*/
			/*									*/
			/************************************/
			case 'TMDB':
				switch ($router->segment(++$segment, true)) {

					case 'GENRES':
						$router->json_response($toolbox->tmdb_genres(
							$router->segment(++$segment),
							$router->segment(++$segment)
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			   VERSIONS 	 		*/
			/*									*/
			/************************************/
			case 'VERSIONS':
				$router->json_response($toolbox->versions());
				break;


			/* Other routes are invalid */
			default: $router->invalid_route(); break;

		}
		break;

	/************************************/
	/*									*/
	/*			DELETE Request			*/
	/*									*/
	/************************************/
	case 'DELETE':
		$token = $user->decode_token($router->getJWT());

		// Check if token is valid
		if ($token['success'] === false) {
			$router->json_response($token['message'], 401);
			exit();
		} else {
			$token = $token['payload'];
		}
		
		switch ($router->segment($segment, true)) {

			/************************************/
			/*									*/
			/*			   PLAYLIST				*/
			/*									*/
			/************************************/
			case 'PLAYLIST':
				$router->json_response($playlist->delete(
					$token->id,
					$router->segment(++$segment)
				));
				break;

			/************************************/
			/*									*/
			/*			   GROUP				*/
			/*									*/
			/************************************/
			case 'GROUP':
				$router->json_response($group->delete(
					$token->id,
					$router->segment(++$segment)
				));
				break;

			/************************************/
			/*									*/
			/*			    LIVE				*/
			/*									*/
			/************************************/
			case 'LIVE':
				$router->json_response($live->delete(
					$token->id,
					$router->segment(++$segment)
				));
				break;

			/************************************/
			/*									*/
			/*			    MOVIE				*/
			/*									*/
			/************************************/
			case 'MOVIE':
				$router->json_response($movie->delete(
					$token->id,
					$router->segment(++$segment)
				));
				break;

			/************************************/
			/*									*/
			/*			    SERIES				*/
			/*									*/
			/************************************/
			case 'SERIES':
				$router->json_response($serie->delete(
					$token->id,
					$router->segment(++$segment)
				));
				break;

			/************************************/
			/*									*/
			/*			 	USERS				*/
			/*									*/
			/************************************/
			case 'USERS':
				switch ($router->segment(++$segment, true)) {

					case 'TICKETS':
						$ids = explode(',', $router->segment(++$segment));
						$router->json_response($user->delete_ticket(
							$token->id,
							count($ids) > 1 ? $ids : $ids[0]
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			 	XD-PRO				*/
			/*									*/
			/************************************/
			case 'XD-PRO':
				switch ($router->segment(++$segment, true)) {

					case 'INSTANCE':
						$router->json_response($xdpro->delete_instance(
							$token->id,
							$router->segment(++$segment)
						));
						break;

					case 'DOWNLOAD':
						$router->json_response($xdpro->delete_download(
							$token->id,
							$router->segment(++$segment)
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			 M3U-2-STRM				*/
			/*									*/
			/************************************/
			case 'M3U-2-STRM':
				switch ($router->segment(++$segment, true)) {

					case 'INSTANCE':
						$router->json_response($m3u2strm->delete_instance(
							$token->id,
							$router->segment(++$segment)
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*				 KODI				*/
			/*									*/
			/************************************/
			case 'KODI':
				switch ($router->segment(++$segment, true)) {

					case 'INSTANCE':
						$router->json_response($kodi->delete_instance(
							$token->id,
							$router->segment(++$segment)
						));
						break;

				}
				break;

			/************************************/
			/*									*/
			/*			 	SIPTV				*/
			/*									*/
			/************************************/
			case 'SIPTV':
				switch ($router->segment(++$segment, true)) {

					case 'PROFILE':
						$router->json_response($siptv->delete_profile(
							$token->id,
							$router->segment(++$segment)
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
	/*			OPTIONS					*/
	/*									*/
	/************************************/
	case 'OPTIONS': http_response_code(200); break;

	/************************************/
	/*									*/
	/*			OTHER Methods			*/
	/*									*/
	/************************************/
	default : $router->invalid_route(); break;

}