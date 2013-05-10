<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/

error_reporting(E_ALL);

$start_time = microtime(true);

require_once (dirname(__FILE__) . '/includes/facebook.php');
require_once (dirname(__FILE__) . '/includes/facebook_php5_photoslib.php');
require_once (dirname(__FILE__) . '/entities/competition.php');
require_once (dirname(__FILE__) . '/entities/picture.php');
require_once (dirname(__FILE__) . '/entities/topic.php');
require_once (dirname(__FILE__) . '/entities/user.php');
require_once (dirname(__FILE__) . '/utilities/analytics.php');
require_once (dirname(__FILE__) . '/utilities/log.php');
require_once (dirname(__FILE__) . '/utilities/ui.php');
require_once (dirname(__FILE__) . '/constants.php');
require_once (dirname(__FILE__) . '/settings.php');

if (isset ($_FILES["uploadedfile"]["tmp_name"])) {
	$destination = time() . $_FILES["uploadedfile"]["name"];

	move_uploaded_file($_FILES["uploadedfile"]["tmp_name"], $UPLOAD_PATH . $destination);
	header("Location: " . $PAGE["COMPETITIONS2"] . "?enter=" . $_REQUEST["enter"] . "&file=" . urlencode($destination));
}

include ($STYLING["PICTURES"]);
include ($STYLING["TEXT"]);
include ($STYLING["TOPMENU"]);
include ($STYLING["COMPETITIONS"]);
include ($STYLING["ALBUMS"]);
include ($STYLING["PICTURE"]);

$facebook = new FacebookPhotos($api_key, $secret);
$uid = $facebook->require_login();

$user = User :: getUser($uid);

if ($user->getStatus() == $STATUS["BANNED"]) {
	echo "<fb:redirect url=\"".$PAGE["BANNED"]."\"/>";
	exit(0);
}

$oldkey = $user->getSessionKey();
if (strcmp($facebook->api_client->session_key, $oldkey) != 0) {
	$user->setSessionKey($facebook->api_client->session_key);
}

echo UI :: RenderMenu($PAGE_CODE["COMPETITIONS2"], $uid);
?>
<br/>
<div class="logo"><a href="http://inspi.re"><img src="<?=$APP_REAL_PATH?>images/logo-medium.gif"></a></div>
<div class="partof">is now part of</div>
<div class="logo2"><a href="http://inspi.re"><img src="<?=$APP_REAL_PATH?>images/inspire_logo_medium.jpg"></a></div>

<div class="explanations">
Over 600 users from Photography Competition have already followed the official migration to <a href="http://inspi.re">http://inspi.re</a>. More competitions, more feedback and a great atmosphere are waiting for you on that brand new website. 
<br/><br/>
Created and run by the same person who brought you Photography Competition, <a href="http://inspi.re">inspi.re</a> is the natural (r)evolution of this Facebook application.
</div>
<?php

echo Analytics::Page("revolution.php");
?>