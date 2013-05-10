<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/

error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');

$start_time = microtime(true);

require_once (dirname(__FILE__) . '/includes/facebook.php');
require_once (dirname(__FILE__) . '/entities/competition.php');
require_once (dirname(__FILE__) . '/entities/picture.php');
require_once (dirname(__FILE__) . '/entities/topic.php');
require_once (dirname(__FILE__) . '/entities/user.php');
require_once (dirname(__FILE__) . '/utilities/analytics.php');
require_once (dirname(__FILE__) . '/utilities/ui.php');
require_once (dirname(__FILE__) . '/constants.php');
require_once (dirname(__FILE__) . '/settings.php');

include ($STYLING["PICTURES"]);
include ($STYLING["TEXT"]);
include ($STYLING["TOPMENU"]);
include ($STYLING["COMPETITIONS"]);
include ($STYLING["PICTURE"]);
include ($STYLING["WALL"]);

$facebook = new Facebook($api_key, $secret);
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

Wall::processWallPost($facebook, $uid); // check if a wall post was made and process it accordingly
echo UI :: RenderMenu($PAGE_CODE["USERS"], $uid);

if (isset($_REQUEST["userid"])) {
	// A userid was specified, we need to display that user's competition profile
	$userid = $_REQUEST["userid"];
	$pictures = Picture::getPictureUserList($userid);
	$pictures_count = count($pictures);
	if ($pictures_count == 0) $pictures_count = "no";
	$pictures_count_text = ($pictures_count > 0?$pictures_count." entries":$pictures_count." entry");
	$result = "<br/><div class=\"basictextblock\">This is <fb:name useyou=\"false\" ifcantsee=\"Anonymous user\" uid=\"$userid\" firstnameonly=\"true\"/>'s competition profile. Below is the list of ".$TEXT["ITEM"]."s <fb:pronoun uid=\"$userid\"/> posted in past competitions.</div>";
	$result .= "<div class=\"competitions\"><div class=\"competition_nolink\">";
	$result .= "<table class=\"competitionrow\"><tr>";
	$result .= "<td class=\"profile_pic\"><fb:profile-pic uid=\"" . $userid . "\" size=\"normal\" linked=\"true\" /></td><td class=\"profile_description\"><h1><fb:name useyou=\"false\" uid=\"$userid\"/></h1>Posted $pictures_count_text on $APP_NAME<br/><br/><a href=\"http://www.facebook.com/addfriend.php?id=$userid\">Add to Friends</a> - <a href=\"http://www.facebook.com/inbox/?compose&id=$userid\">Send Message</a> - <a href=\"http://www.facebook.com/poke.php?id=$userid\">Poke!</a> - <a href=\"http://www.facebook.com/friends/?id=$userid\">View Friends</a></td>";
	$result .= "</tr></table>";
	$result .= "</div>";
	
	$topiclist = array();
	foreach ($pictures as $topic_id => $picture_id) {
		$competition = Competition :: getCompetition($topic_id);
		if ($competition->getStatus() == $STATUS["CLOSED"])
		$topiclist [$topic_id]= $competition->getStartTime();
	}
	arsort($topiclist);
	
	
	foreach ($topiclist as $topic_id => $start_time) {
		$topic = Topic :: getTopic($topic_id);
		$picture_id = $pictures[$topic_id];
		$result .= "<div onClick=\"document.setLocation('".$PAGE["WINNERS"]."?topic=$topic_id&picture=$picture_id');\" class=\"competition\">";
		$result .= "<table class=\"competitionrow\"><tr>";
		$result .= "<td class=\"winners_pic\"><fb:photo pid=\"$picture_id\" size=\"small\"/></td>";
		$result .= "<td class=\"winners_description\"><h1>".$topic->getTitle()."</h1>";
		if (strcmp($topic->getDescription(), "") != 0) $result .= "<div class=\"topic_description\">".$topic->getDescription()."</div>";
		$result .= "suggested by <a href=\"".$PAGE["USERS"]."?userid=".$topic->getUserId()."\"><fb:name ifcantsee=\"Anonymous user\" uid=\"" . $topic->getUserId() . "\" /></a>";
		
		$result .= "</td></tr></table>";
		$result .= "</div>";
	}
	$result .= "</div>";
	echo $result;
} else {
	// No userid specified, display the list of users
}

echo Analytics::Page("users.php");
?>