<?php

/**********************************************************************************/
/*																				  */
/*				const.php [ Constants ]		  									  */
/*																				  */
/*				Author	: Ernst Reidinga 										  */
/*				Date 	: 12/04/2021									  		  */
/*				Version	: 1.0												  	  */
/*																				  */
/**********************************************************************************/

/* Router Root */
define('ROOT_SEGMENT', 0);

/* SQL Database */
define('SQL', array(
	'app' => array(
		'server'	=> 'localhost',
		'username'	=> 'iptvtool_app',
		'password'	=> 'u8&o@K.fcGS^Z!;}Wy',
		'dbname'	=> 'iptvtool_app'
	)
));

/* Back-Up ZIP Password */
define('ZIP_PASSWORD', 'xv33XV2P2CThAqvncpXMcYW97SKgDb');

/* Server Key - used to sign the JWT */
define('SERVER_KEY', '11dd53eacc7b334c8d9bfc5d0a4b5f03');

/* TMDB API KEY */
define('TMDB_API_KEY', 'af538ec9bad9dba979b60eda62532e2d');

/* EMAIL */
define('EMAIL', array(
	'url'   => 'https://api.mailersend.com/v1/email',
	'auth'  => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiOThlOTI3ZTI4ZmNjYWY5ZjQzYzAzMzhhYjJhYzhlZmVjM2VkOTE5ZTEyOWEyNzM0YzU5NDhjMGQ0NTMzMjVmZDFkMGNkOTVmYTgwY2VjZWQiLCJpYXQiOjE2MjU3NTM3MzgsIm5iZiI6MTYyNTc1MzczOCwiZXhwIjo0NzgxNDI3MzM4LCJzdWIiOiI4OTUyIiwic2NvcGVzIjpbImVtYWlsX2Z1bGwiLCJkb21haW5zX2Z1bGwiLCJhY3Rpdml0eV9mdWxsIiwiYW5hbHl0aWNzX2Z1bGwiLCJ0b2tlbnNfZnVsbCIsIndlYmhvb2tzX2Z1bGwiLCJ0ZW1wbGF0ZXNfZnVsbCJdfQ.jx4Nd4hZo6kopUzsELEIlkVXRto0k6AxQyBvsW9eTS6rN6atdISAbMj1gdgyShAzBwtBwLPBJjzDfl1l5vIhvp5A2348Z8oKJzOBjk3j9u_xZPnK73bJbfhP7mpJNYmY7ULaJ2RSUoWUk7BuO_jPStF4SuPr_RM7zvS9vxCLoiCPLXD4MZK9MG08UoEg_PNZmWLDjvl13GZ9sk5sJx77d4zPApSiNXXnbQzllWIV3vy73TjVSO3ofT2ow2iH6aJHlpXcYvy2oGbe7Q381sT7Kq1jIJ7Xvode2RsoE1YUhXiWW19caokvbFO-6eC7OjvlAsyg-DvWJlklpMspSg7g7t8DmSVCyk-NZFVsDpdkvYE6gpoi20FIVvzE-mPpMZYzjSFK5APq5PBJZjMVOXeuKVX-RsAgk_O-hu0vXk7L5oTJ5eGRinD_hzSBkY4_-51pPqvmpHJF9UiAnG75ygzNSiGfNIm7a4CnUoBm0YoBpdQNdjGo8tn2xTaB0ZN3yQJYm_Du-XCPXEuPfhwncsx-evQn0UfrqlEsMyoRZxAsPx0mdbmKpFd2dJYJGxt4KsWXrD7O_EmDfHfjPw82n4zki7oPz3WviKPxY-y4z1l5BKe_3EP_R-bavJgqumGXJbsWzjGK3BPvNyLmQTJwZUc4GBjlQ3eCgTzJAjVznFrVJPs',
	'email' => 'noreply@iptv-tools.com'
));