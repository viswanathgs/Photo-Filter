<?php
require_once('sdk/src/facebook.php');
require_once('AppInfo.php');
require_once('session_config.php');

// Enforce https on production
if (substr(AppInfo::getUrl(), 0, 8) != 'https://' && $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
	header('Location: https://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
	exit();
}

$facebook = new Facebook(array(
	'appId'  => AppInfo::appID(),
	'secret' => AppInfo::appSecret(),
));
$_SESSION['facebook'] = $facebook;

$user = $facebook->getUser();

//header('Location: https://www.facebook.com/dialog/oauth?client_id='.AppInfo::appID().'&redirect_uri='.urlencode('https://electric-mountain-8719.herokuapp.com/login.php/').'&scope=user_photos'); 
?>

<!DOCTYPE html>
<html xmlns:fb="http://www.facebook.com/2008/fbml">
<head>
  <title>Photo Filter</title>
	
	<link rel="stylesheet" type="text/css" href="stylesheets/base.css" />
</head>

<body>

  <div id="header">
    <h1>Photo Filter</h1> <br />
    <div id="desc">Find photos you are tagged with your friends</div>
  </div>

<?php
	if ($user) {
		// User is logged in
		try {
			$permissions = $facebook->api('/me/permissions');
			if (array_key_exists('user_photos', $permissions['data'][0])) {
				// Permissions ok
				$_SESSION['access_token'] = $facebook->getAccessToken();
				$_SESSION['user'] = $facebook->getUser();
				header('Location: main.php');	
			}
			else {
				$loginUrl = $facebook->getLoginUrl(array('scope' => 'user_photos'));
	  	  echo "<a style='float:right' href='".$loginUrl."'>Login</a>";
			}
		}
		catch (FacebookApiException $e) {
			$loginUrl = $facebook->getLoginUrl(array('scope' => 'user_photos'));
			echo "<a style='float:right' href='".$loginUrl."'>Login</a>";
		}
	}
	else {
		// Provide Login Url
		$loginUrl = $facebook->getLoginUrl(array('scope' => 'user_photos'));
		echo "<a style='float:right' href='".$loginUrl."'>Login</a>";
	}
?>      

</body>

</html>
