<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/

error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');

$start_time = microtime(true);

require_once (dirname(__FILE__) . '/includes/facebook.php');
require_once (dirname(__FILE__) . '/entities/user.php');
require_once (dirname(__FILE__) . '/entities/topic.php');
require_once (dirname(__FILE__) . '/entities/picture.php');
require_once (dirname(__FILE__) . '/entities/picturevote.php');
require_once (dirname(__FILE__) . '/entities/competition.php');
require_once (dirname(__FILE__) . '/entities/wall.php');
require_once (dirname(__FILE__) . '/utilities/analytics.php');
require_once (dirname(__FILE__) . '/utilities/ui.php');
require_once (dirname(__FILE__) . '/constants.php');
require_once (dirname(__FILE__) . '/settings.php');

include ($STYLING["TEXT"]);
include ($STYLING["TOPMENU"]);
include ($STYLING["PICTURE"]);
include ($STYLING["ALBUMS"]);
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

echo UI :: RenderMenu($PAGE_CODE["YOUR_SUBMISSIONS"], $uid);
Wall::processWallPost($facebook, $uid); // check if a wall post was made and process it accordingly

function RenderPageLinks($count, $current) {
   global $PAGE;
   $result = "";
   
   for ($i = 1; $i <= $count; $i++) {
       if ($current == $i)
          $result .= "$i ";
       else
          $result .= "<a href=\"".$PAGE["YOUR_SUBMISSIONS"]."?page=".$i."\">$i</a> ";
   }
   return $result;
}

function RenderSubmissions() {
	global $APP_REAL_PATH;
	global $PAGE;
	global $uid;
	global $COMPETITION_DURATION;
	global $STATUS;
	global $TEXT;
        
        $result = "";
        
        $submissions = Picture :: getPictureUserList($uid);
        $navigation = RenderPageLinks(ceil((count($submissions) / 10)), (isset($_REQUEST["page"])?$_REQUEST["page"]:1));
        if (count($submissions) > 10)
        $result .= "<div class=\"navigatepagestop\">Page: ".$navigation."</div>";
         
	$result .= "<div class=\"albums\">";
        
	if (empty ($submissions))
		$result .= "<div class=\"album\">You haven't entered any competition.</div>";

	$competitions = array ();
	$pictures = array ();
	foreach ($submissions as $topic => $picture) {
		$competitions[$topic] = Competition :: getCompetition($topic);
		$pictures[$topic] = $competitions[$topic]->getStartTime();
	}

	arsort($pictures);
        
        $start_entry = 0;
        if (isset($_REQUEST["page"])) $start_entry = ($_REQUEST["page"] - 1)*10;
        
        $pictures = array_slice($pictures, $start_entry, 10, true);

	foreach ($pictures as $topic_id => $start_time) {
		$topic = Topic :: getTopic($topic_id);
		$divresult = "<div onClick=\"document.setLocation('" . $PAGE["YOUR_SUBMISSIONS"] . "?topic=" . $topic_id . "&picture=" . $submissions[$topic_id] . "');\" class=\"album\"><table class=\"albumrow\"><tr>";
		$divresult .= "<td class=\"albumpic\"><fb:photo pid=\"" . $submissions[$topic_id] . "\" size=\"small\"/></td>";
		$divresult .= "<td class=\"albumdescription\"><h2>" . $topic->getTitle() . "</h2>";
		$divresult .= " suggested by <fb:name ifcantsee=\"Anonymous user\" uid=\"" . $topic->getUserId() . "\" /><br/>";
		$votelist = PictureVote :: getPictureVoteList($submissions[$topic_id], $topic_id);
		$votes = count($votelist);
		$score = 0;
		foreach ($votelist as $vote)
			$score += $vote;

		if ($votes == 0)
			$votes = "No";
		//	$divresult .= "<b>".$votes . "</b> vote" . ($votes == 1 ? "" : "s") . " " . ($votes == 1 ? "was" : "were") . " cast on this photo";
		$divresult .= "<b>" . $votes . "</b> vote" . ($votes == 1 ? "" : "s") . " " . ($votes == 1 ? "was" : "were") . " cast on this ".$TEXT["ITEM"].", bringing it to a score of <b>" . $score . "</b>";

		if ($competitions[$topic_id]->getStatus() == $STATUS["CLOSED"]) {

			$ranks = $competitions[$topic_id]->getRanks();
			if (isset ($ranks[$submissions[$topic_id]])) {
				$total = count($ranks);

				$divresult .= "<br/><br/>It ranked <b>" . $ranks[$submissions[$topic_id]] . "</b> out of $total in the competition";
			}
		}
		$divresult .= "</td></tr></table></div>";
		$result .= $divresult;
	}
	$result .= "</div>";
	return $result;
}

if (isset ($_REQUEST["topic"]) && isset ($_REQUEST["picture"])) {
	$topic = Topic :: getTopic($_REQUEST["topic"]);
	$picture = Picture :: getPicture($_REQUEST["picture"], $_REQUEST["topic"]);
	$competition = Competition :: getCompetition($_REQUEST["topic"]);

	if ($uid != $picture->getUserId()) {
		echo "<fb:error message=\"You are not allowed to access this page. Only the author of this ".$TEXT["ITEM"]." can.\"/>";
	} else {

		if (isset ($_REQUEST["delete"])) {
			$picture->setStatus($STATUS["DELETED"]);
			echo "<fb:success message=\"This entry for the " . $topic->getTitle() . " competition was succesfully deleted.\"/>";
		}
		elseif (isset ($_REQUEST["error"])) {
			echo "<fb:error message=\"Couldn't enter this ".$TEXT["ITEM"]." because it's only visible to you and your friends! Please change the privacy settings of the ".$TEXT["ITEM"].". To do this, go to Facebook's photo app, look for the album this ".$TEXT["ITEM"]." is from, click Edit Album, then Edit Info and you should see the options for who can access the album.\"/>";

		} else {
			echo "<br/>";
			echo "<fb:dialog id=\"my_dialog\">  <fb:dialog-title>Are you sure do you want to delete this ".$TEXT["ITEM"]."?</fb:dialog-title> <fb:dialog-content><form id=\"my_form\">Please confirm if you want to delete this ".$TEXT["ITEM"]."</form></fb:dialog-content>  <fb:dialog-button type=\"button\" value=\"Yes\" href=\"" . $PAGE['YOUR_SUBMISSIONS'] . "?topic=" . $_REQUEST["topic"] . "&picture=" . $_REQUEST["picture"] . "&delete=true\" /> <fb:dialog-button type=\"button\" value=\"No\" href=\"" . $PAGE['YOUR_SUBMISSIONS'] . "?topic=" . $_REQUEST["topic"] . "&picture=" . $_REQUEST["picture"] . "\" /> </fb:dialog>";
			echo "<div class=\"basictextblock\">You've entered the picture below in the <b>" . $topic->getTitle() . "</b> competition. <a href=\"#\" clicktoshowdialog=\"my_dialog\">Click here</a> to delete this entry.<br/>
										</div>";
		}

		if (isset ($_REQUEST["comments"])) {
			if ($_REQUEST["comments"] == 1) {
				$picture->setCommentsAllowed(1);
				echo "<fb:success message=\"This entry for the " . $topic->getTitle() . " had its comments successfully enabled.\"/>";
			} else {
				$picture->setCommentsAllowed(0);
				echo "<fb:success message=\"This entry for the " . $topic->getTitle() . " had its comments successfully disabled.\"/>";
			}
		}

		echo "<div class=\"pictures\">";
		echo "<div class=\"picture\">";
		echo "<fb:photo pid=\"" . $_REQUEST["picture"] . "\" size=\"normal\"/><br/>";

		if ($picture->getCommentsAllowed()) {
			echo "<a href=\"" . $PAGE["YOUR_SUBMISSIONS"] . "?topic=" . $_REQUEST["topic"] . "&picture=" . $_REQUEST["picture"] . "&comments=0\">Disable comments on this ".$TEXT["ITEM"]."</a>";
		} else {
			echo "<a href=\"" . $PAGE["YOUR_SUBMISSIONS"] . "?topic=" . $_REQUEST["topic"] . "&picture=" . $_REQUEST["picture"] . "&comments=1\">Enable comments on this ".$TEXT["ITEM"]."</a>";
		}

		echo "</div>";
		echo "</div>";

		if ($picture->getCommentsAllowed())
			echo UI :: RenderWall("Discuss this ".$TEXT["ITEM"], $_REQUEST["picture"], $_REQUEST["topic"], $PAGE["YOUR_SUBMISSIONS"], $competition->getStatus()  != $STATUS["CLOSED"], $picture->getUserId(), $uid);

	}
} else {
	//echo "<br/>";
	/*echo "<div class=\"basictextblock\">Below are all the ".$TEXT["ITEM"]."s you've entered in competitions before.<br/>
				</div>"; */
	echo RenderSubmissions();
}

echo Analytics::Page("yoursubmissions.php");
?>