<?php
require_once('sdk/src/facebook.php');
require_once('AppInfo.php');
require_once('session_config.php');

// Enforce https on production
if (substr(AppInfo::getUrl(), 0, 8) != 'https://' && $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
	header('Location: https://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
	exit();
}

if (!isset($_SESSION['access_token']) || !isset($_SESSION['facebook']) || !isset($_SESSION['user'])) {
	header('Location: index.php');
}
$facebook = $_SESSION['facebook'];
$access_token = $_SESSION['access_token'];

try {
	$all_friends = $facebook->api('/me/friends?access_token='.$access_token.'&limit=0');
	$all_friends_json = json_encode($all_friends['data']);

	$all_photos = $facebook->api('/me/photos?access_token='.$access_token.'&limit=0');
	$all_photos_json = json_encode($all_photos['data']);
}
catch (FacebookApiException $e) {
	header('Location: index.php');
}

?>

<!DOCTYPE html>
<html xmlns:fb="http://www.facebook.com/2008/fbml">
<head>
	<title>Photo Filter</title>

	<link rel="stylesheet" type="text/css" href="stylesheets/base.css" />
	<link rel="stylesheet" type="text/css" href="stylesheets/token-input.css" />
	<link rel="stylesheet" type="text/css" href="stylesheets/token-input-facebook.css" />

	<script type="text/javascript" language="javascript" src="javascript/jquery-1.7.1.min.js"></script>
	<script type="text/javascript" language="javascript" src="javascript/jquery.tokeninput.js"></script>

	<script type="text/javascript" language="javascript">

	function filter_server() {
		// Each new search retrieves photos from server
		if ($("#friends").val() == "") {
			$("#result").html("<div class='info'>No photos found</div>");
			return;
		}

		$("#result").html("<div class='info'>Searching...</div>");		
		var friends = $("#friends").val();

		$.ajax({
			url: 'filter.php',
			type: 'post',
			data: "q="+friends,
			success: function(response) {
				var photos = eval('(' + response + ')');
				var result = "";
				for (var key in photos) {
					if (photos.hasOwnProperty(key)) {
						result += "<div class='thumbnail'>";
						result += "<a href='";
						result += photos[key].link;
						result += "'>";
						result += "<img class='imagedropshadow' src='";
						result += photos[key].picture;
						result += "' />";						
						result += "</a>";
						result += "</div>";
					}
				}
				if (result == "") {
					result = "<div class='info'>No photos found</div>";
				}
				$("#result").html(result);	
			}
		});
	}

	function filter_cached() {
		// User's photos are cached when the app is initialized
		if ($("#friends").val() == "") {
			$("#result").html("<div class='info'>No photos found</div>");
			return;
		}

		$("#result").html("<div class='info'>Searching...</div>");
		var alltags = $("#alltags").is(":checked");
		var friends = $("#friends").val().split(',');

//		var all_photos_json = <?php echo json_encode($all_photos_json); ?>;
//		var all_photos = eval('(' + all_photos_json + ')');
		var all_photos = <?php echo $all_photos_json; ?>;

		var no_photo_found = true;
		for (var key in all_photos) {
			if (!all_photos.hasOwnProperty(key)) {
				continue;
			}

			if (valid_photo(all_photos[key], friends, alltags)) {
				var result = "<div class='thumbnail'>";
				result += "<a href='";
				result += all_photos[key].link;
				result += "'>";
				result += "<img class='imagedropshadow' src='";
				result += all_photos[key].picture;
				result += "' />";           
				result += "</a>";
				result += "</div>";

				if (no_photo_found) {
					no_photo_found = false;
					$("#result").html(result);
				}
				else {
					$("#result").append(result);
				}
			}
		}

		if (no_photo_found) {
			$("#result").html("<div class='info'>No photos found</div>");	
		}
	}

	function valid_photo(photo, friends, alltags) {

		var tagcount = 0;
		for (var key in photo.tags.data) {
			if (!photo.tags.data.hasOwnProperty(key)) {
				continue;
			}

			for (var i = 0; i < friends.length; i++) {
				if (friends[i] == photo.tags.data[key].id) {
					tagcount++;
					break;
				}
			}
			if (!alltags && tagcount) {
				return true;
			}	
		}

		if ((alltags && tagcount == friends.length) || (!alltags && tagcount)) {
			return true;
		}
		return false;
	}

	</script>		
</head>

<body>
	<div id="header">
		<h1>Photo Filter</h1> <br />
		<div id="desc">Find photos you are tagged with your friends</div>
	</div>

	<a style="float:right" href=" <?php echo $facebook->getLogoutUrl(); ?> ">Logout</a>

	<div id="input">
		<input type="text" id="friends" /> <br />
		<input type="checkbox" id="alltags" value="alltags" /> Only display photos that contain all the tags <br /> <br />
		<button onclick="filter_cached()" id="filter_button">Filter Photos</button> <br />

		<script type="text/javascript">

			$(document).ready(function() {
				$("#friends").tokenInput(
					<?php echo $all_friends_json; ?>, 
					{	
						theme: "facebook"	
				});
			});

		</script>

	</div>

	<div id="result">
	</div>
</body>

</html>
