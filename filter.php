<?php
require_once('sdk/src/facebook.php');
require_once('AppInfo.php');
require_once('session_config.php');

// Enforce https on production
if (substr(AppInfo::getUrl(), 0, 8) != 'https://' && $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
	header('Location: https://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
	echo "";
}

if (!isset($_SESSION['facebook']) || !isset($_SESSION['access_token']) || !isset($_SESSION['user'])) {
  echo "";
}

$facebook = $_SESSION['facebook'];
$access_token = $_SESSION['access_token'];

$friends = explode(',', $_POST['q']);

$all_photos = $facebook->api('/me/photos?access_token='.$access_token.'&limit=0');
/*
while ($all_photos['paging']) {
	if (!$all_photos['paging']['next']) {
		break;
	}
	$all_photos = $facebook->api(substr($all_photos['paging']['next'], 26));
	$total = $total.",".count($all_photos['data']);
}
*/

$photos = array();
foreach ($all_photos['data'] as $photo) {
	$valid = false;

	foreach ($photo['tags']['data'] as $tag) {
		foreach ($friends as $friend) {
			if ($tag['id'] == $friend) {
				$valid = true;
				break;
			}
		}
		if ($valid == true) {
			break;
		}
	}

	if ($valid == true) {
		array_push($photos, $photo);
	}
}

echo json_encode($photos);
?>
