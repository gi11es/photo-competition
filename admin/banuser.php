<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/

require_once (dirname(__FILE__).'/../includes/facebook.php');
require_once(dirname(__FILE__)."/../entities/picture.php");
require_once(dirname(__FILE__)."/../entities/picturevote.php");
require_once(dirname(__FILE__)."/../entities/user.php");
require_once(dirname(__FILE__)."/../utilities/cache.php");
require_once(dirname(__FILE__).'/../utilities/ui.php');
require_once(dirname(__FILE__)."/../constants.php");
require_once(dirname(__FILE__)."/../settings.php");

error_reporting(E_ALL);

$facebook = new Facebook($api_key, $secret);
$uid = $facebook->require_login();

echo UI::RenderMenu(null, $uid);

error_reporting(E_ALL);

if (isset($_REQUEST["user_id"]) && isset($ADMINS[$uid])) {
	$user = User::getUser($_REQUEST["user_id"]);
	
	$pictures = Picture::getPictureUserList($user->getId());
	foreach ($pictures as $topic_id => $picture_id) {
		$votes = PictureVote::getPictureVoteList($picture_id, $topic_id);
		foreach ($votes as $voter_id => $score) {
			PictureVote::deletePictureVote($picture_id, $topic_id, $voter_id);
			echo "Vote on picture $picture_id deleted<br/>";
		}
		$picture = Picture::getPicture($picture_id, $topic_id);
		$picture->setStatus($STATUS["DELETED"]);
		echo "Picture $picture_id deleted<br/>";
	}
	
	$user->setStatus($STATUS["BANNED"]);
	echo "User with id=".$_REQUEST["user_id"]." banned<br/>";
}

?>
