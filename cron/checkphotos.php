#!/usr/local/bin/php
<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/

// This script check regularly if some photos have been deleted or their permissions turned wrong

$start_time = microtime(true);

require_once (dirname(__FILE__) . '/../includes/facebook.php');
require_once (dirname(__FILE__) . '/../entities/competition.php');
require_once (dirname(__FILE__) . '/../entities/picture.php');
require_once (dirname(__FILE__) . '/../entities/picturevote.php');
require_once (dirname(__FILE__) . '/../entities/user.php');
require_once (dirname(__FILE__) . '/../utilities/cache.php');
require_once (dirname(__FILE__) . '/../utilities/ui.php');
require_once (dirname(__FILE__) . '/../constants.php');
require_once (dirname(__FILE__) . '/../settings.php');

$facebook = new Facebook($api_key, $secret);

$competitions = Competition :: getOpenCompetitionList();

foreach ($competitions as $topic_id => $start_time) {
	$pictures = Picture::getPictureTopicList($topic_id);

	// Check if the user deleted the photo

	foreach ($pictures as $user_id => $picture_id) {
		$user = User :: getUser($user_id);
		$session_key = $user->getSessionKey();
		try {
			$facebook->set_user($user_id, $session_key);
			$photo = $facebook->api_client->photos_get(null, null, $picture_id);
		} catch (Exception $e) {
			$photo = true;
		}

		if (!$photo) {
			$pic = Picture :: getPicture($picture_id, $topic_id);
			$pic->setStatus($STATUS["DELETED"]);
			try {
				$topic = Topic :: getTopic($topic_id);
				echo "Photo with id $picture_id is dead, sending notification\r\n";
				$facebook->api_client->notifications_send($user_id, ", you've deleted a photo from <a href=\"http://www.facebook.com/photos.php\">Facebook's photos application</a>. Since you were using it in the <b>" . $topic->getTitle() . "</b> competition, your entry in that competition was automatically deleted. Any changes you make (deletion, permissions change) in <a href=\"http://www.facebook.com/photos.php\">Facebook's photos application</a> affects $APP_NAME, be careful with what you do!", "app_to_user");
			} catch (Exception $e) {}
		}
	}

	$user = User :: getUser("681686522");
	$session_key = $user->getSessionKey();
	$facebook->set_user("681686522", $session_key);

	foreach ($pictures as $user_id => $picture_id) {
		try {
			$photo = $facebook->api_client->photos_get(null, null, $picture_id);
		} catch (Exception $e) {
			$photo = true;
		}

		if (!$photo) {
			$pic = Picture :: getPicture($picture_id, $topic_id);
			$pic->setStatus($STATUS["DELETED"]);
			try {
				$topic = Topic :: getTopic($topic_id);
				echo "Photo with id $picture_id has wrong permissions, sending notification\r\n";
				$facebook->api_client->notifications_send($user_id, ", you've submitted a photo from <a href=\"http://www.facebook.com/photos.php\">Facebook's photos application</a> that now has too strict privacy settings, facebook users that aren't your friends can't see it. Since you were using it in the <b>" . $topic->getTitle() . "</b> competition, your entry in that competition was automatically deleted. Any changes you make (deletion, permissions change) in <a href=\"http://www.facebook.com/photos.php\">Facebook's photos application</a> affects $APP_NAME, be careful with what you do!", "app_to_user");
			} catch (Exception $e) {}
		}
	}
	}
?>
