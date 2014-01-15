<?php

	ini_set('session.gc_maxlifetime', 86400);
	session_start();

	if(!$_SESSION['valid']){
		$_SESSION['rated'] = array();
		$_SESSION['valid'] = true;
	}
	$rated = count($_SESSION['rated']);
	$index = $rated - 1;

?>
<!DOCTYPE html>
<html lang="en" ng-app="app" manifest="the.appcache">
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>Hunk or Skunk</title>
		<link rel="stylesheet" href="css/master.css" />
	</head>
	<body ng-init="guy=<?php if($rated > 0){ echo $_SESSION['rated'][$index]; }else{ echo 0; } ?>; <?php if($rated == 5){ echo 'hideRate=true;'; } ?>">
		<nav class="navbar navbar-inverse navbar-static-top" role="navigation">
 			<div class="navbar-header">
				<a class="navbar-brand" href="#"><div class="fa fa-thumbs-o-up"></div> Hunk-or-Skunk <div class="fa fa-thumbs-o-down"></div></a>
			</div>
		</nav>
		<div ng-view>Loading...</div>
		<script src="js/hunkorskunk.js"></script>
	</body>
</html>