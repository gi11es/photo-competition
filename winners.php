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
require_once (dirname(__FILE__) . '/entities/user.php');
require_once (dirname(__FILE__) . '/entities/picture.php');
require_once (dirname(__FILE__) . '/entities/wall.php');
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
echo UI :: RenderMenu($PAGE_CODE["WINNERS"], $uid);

function RenderPictureGrid($pictures, $columns = 4) {
	global $PAGE;
	global $_REQUEST;
	global $uid;

	$thumb_count = 0;
	echo "<table class=\"thumbnails\">";
	if (empty ($pictures))
		echo "<tr><td>No entries in this competition</td></tr>";
	else {

		foreach ($pictures as $picture) {

			$thumb_count++;
			if ($thumb_count % $columns == 1)
				echo "<tr>";
			echo "<td><a href=\"" . $PAGE["WINNERS"] . "?topic=" . $_REQUEST["topic"] . "&picture=" . $picture . "&key=" . md5($uid) . "\" class=\"thumbnail\"><fb:photo pid=\"" . $picture . "\" size=\"small\"/></a></td>";
			if ($thumb_count % $columns == 0)
				echo "</tr>";
		}

		for ($i = 0; $i < $columns - ($thumb_count % $columns); $i++)
			echo "<td></td>";
		if ($thumb_count % $columns != 0)
			echo "</tr>";
	}
	echo "</table>";
}

function RenderPageLinks($count, $current) {
   global $PAGE;
   $result = "";
   
   for ($i = 1; $i <= $count; $i++) {
       if ($current == $i)
          $result .= "$i ";
       else
          $result .= "<a href=\"".$PAGE["WINNERS"]."?page=".$i."\">$i</a> ";
   }
   return $result;
}

function RenderClosedCompetitions() {
	global $APP_REAL_PATH;
	global $PAGE;
	global $uid;
	global $COMPETITION_DURATION;
	global $TEXT;
        global $_REQUEST;

	$result = "<div class=\"basictextblock\">Below are the winning entries of the past competitions. Click on the thumbnails to see the original size ".$TEXT["ITEM"]."s.<br/>
									</div>";
 	$competitions = Competition :: getClosedCompetitionList();
        
        arsort($competitions);
        
        $navigation = RenderPageLinks(ceil((count($competitions) / 10)), (isset($_REQUEST["page"])?$_REQUEST["page"]:1));
    
        $result .= "<div class=\"navigatepagestop\">Page: ".$navigation."</div>";
    
	$result .= "<div class=\"competitions\">";
	if (empty ($competitions))
		$result .= "<div class=\"competition_nolink\">No competitions have finished yet.</div>";
        
        $start_competition = 0;
        if (isset($_REQUEST["page"])) {
            $start_competition = ($_REQUEST["page"] - 1)*10;
        }
        $competitions = array_slice($competitions, $start_competition, 10, true);

	foreach ($competitions as $topic_id => $start_time) {
		$topic = Topic :: getTopic($topic_id);
		$competition = Competition :: getCompetition($topic_id);
		$pictures_count = count(Picture :: getPictureTopicList($topic_id));
		$ranks = $competition->getRanks(false);
		$winners = array ();
		$second = array ();
		$third = array ();

		$lookfornextwinner = false;
		$lookforscore = 1;
		foreach ($ranks as $picture => $score) {
			if ($lookfornextwinner) {
				$lookforscore = $score;
				$lookfornextwinner = false;
			}
			if ($score == $lookforscore) {
				$pic = Picture :: getPicture($picture, $topic_id);
				if (strcmp($topic->getUserId(), $pic->getUserId()) != 0)
					$winners[$pic->getUserId()] = $picture;
				else
					$lookfornextwinner = true;
			}
		}

		$secondscorefound = false;
		$lookforsecondscore = 0;
		foreach ($ranks as $picture => $score) {
			if ($lookforscore < $score && !$secondscorefound) {
				$lookforsecondscore = $score;
				$secondscorefound = true;
			}
			if ($lookforsecondscore != 0 && $score == $lookforsecondscore) {
				$pic = Picture :: getPicture($picture, $topic_id);
				if (strcmp($topic->getUserId(), $pic->getUserId()) != 0)
					$second[$pic->getUserId()] = $picture;
				else
					$secondscorefound = false;
			}
		}

		$thirdscorefound = false;
		$lookforthirdscore = 0;
		foreach ($ranks as $picture => $score) {
			if ($lookforsecondscore < $score && !$thirdscorefound) {
				$lookforthirdscore = $score;
				$thirdscorefound = true;
			}
			if ($lookforthirdscore != 0 && $score == $lookforthirdscore) {
				$pic = Picture :: getPicture($picture, $topic_id);
				if (strcmp($topic->getUserId(), $pic->getUserId()) != 0)
					$third[$pic->getUserId()] = $picture;
				else
					$thirdscorefound = false;
			}
		}

		$winner_count = count($winners);
		$winner_string = "";
		$pictures_string = "";
		foreach ($winners as $user_id => $picture) {
			$winner_string .= "<a href=\"".$PAGE["USERS"]."?userid=".$user_id."\"><fb:name linked=\"false\" ifcantsee=\"Anonymous user\" uid=\"" . $user_id . "\" /></a>";
			$pictures_string .= "<a href=\"" . $PAGE["WINNERS"] . "?topic=" . $topic_id . "&picture=" . $picture . "\"><fb:photo pid=\"" . $picture . "\" size=\"small\"/></a>";
			if ($winner_count > 1) {
				$winner_string .= ", ";
				$pictures_string .= "<br/><br/>";
			}

			$winner_count--;
		}

		$second_count = count($second);
		$second_string = "";
		foreach ($second as $user_id => $picture) {
			$second_string .= "<a href=\"".$PAGE["USERS"]."?userid=".$user_id."\"><fb:name linked=\"false\" ifcantsee=\"Anonymous user\" uid=\"" . $user_id . "\" /></a> (<a href=\"" . $PAGE["WINNERS"] . "?topic=" . $topic_id . "&picture=" . $picture . "\">see entry</a>)";
			if ($second_count > 1) {
				$second_string .= ", ";
			}
			$second_count--;
		}

		$third_count = count($third);
		$third_string = "";
		foreach ($third as $user_id => $picture) {
			$third_string .= "<a href=\"".$PAGE["USERS"]."?userid=".$user_id."\"><fb:name linked=\"false\" ifcantsee=\"Anonymous user\" uid=\"" . $user_id . "\" /></a> (<a href=\"" . $PAGE["WINNERS"] . "?topic=" . $topic_id . "&picture=" . $picture . "\">see entry</a>)";
			if ($third_count > 1) {
				$third_string .= ", ";
			}
			$third_count--;
		}

		$divresult = "<div class=\"competition_nolink\"><table class=\"competitionrow\"><tr>";
		$divresult .= "<td class=\"winners_pic\">" . $pictures_string . "</td>";
		$divresult .= "<td class=\"winners_description\"><h1>" . $topic->getTitle() . "</h1>";
		if (strcmp($topic->getDescription(), "") != 0) $divresult .= "<div class=\"topic_description\">".$topic->getDescription()."</div>";
		$divresult .= "suggested by <a href=\"".$PAGE["USERS"]."?userid=".$topic->getUserId()."\"><fb:name linked=\"false\" ifcantsee=\"Anonymous user\" uid=\"" . $topic->getUserId() . "\" /></a>, <b>won by ";
		$divresult .= $winner_string . "</b>";
		
		$divresult .= "<br/><br/>2nd place by " . $second_string;
		$divresult .= "<br/>3rd place by " . $third_string;
		$divresult .= "<br/><a href=\"" . $PAGE["WINNERS"] . "?topic=" . $topic_id . "\"><b>See all $pictures_count entries</b></a>";
		$divresult .= "</td></tr></table></div>";
		$result .= $divresult;
	}
	$result .= "</div>";
        
        $result .= "<div class=\"navigatepagesbottom\">Page: ".$navigation."</div>";
        
	return $result;
}
?>
<br/>

<?php


if (isset ($_REQUEST["topic"]) && !isset ($_REQUEST["picture"])) {
	// If only the topic is specified as an argument, we display a link to all the entries

	RenderPictureGrid(Picture :: getPictureTopicList($_REQUEST["topic"]));

}
elseif (isset ($_REQUEST["picture"])) {
	// If the photo id is also specified we display a page with the big sized photo and comments
	$picture = Picture :: getPicture($_REQUEST["picture"], $_REQUEST["topic"]);
	$topic = Topic :: getTopic($_REQUEST["topic"]);

	echo "<div class=\"basictextblock\"><a href=\"".$PAGE["USERS"]."?userid=".$picture->getUserId()."\"><fb:name linked=\"false\" ifcantsee=\"Anonymous user\" uid=\"" . $picture->getUserId() . "\" /></a> entered this ".$TEXT["ITEM"]." in the <a href=\"" . $PAGE["WINNERS"] . "?topic=" . $_REQUEST["topic"] . "\"><b>" . $topic->getTitle() . "</b></a> competition.<br/>
										</div>";
	echo "<div class=\"pictures\">";
	echo "<div class=\"picture\">";
	echo "<fb:photo pid=\"" . $_REQUEST["picture"] . "\" size=\"normal\"/>";
	if ($picture->getCommentsAllowed() == 0)
		echo "<br/><br/><b>The comments were disabled on this entry by the ".$TEXT["ITEM"]."'s author.</b>";

		
	echo "</div>";
	echo "</div>";
			
	if ($picture->getCommentsAllowed() == 1)
		echo UI :: RenderWall("Discuss this ".$TEXT["ITEM"], $_REQUEST["picture"], $_REQUEST["topic"], $PAGE["WINNERS"], false, $picture->getUserId(), $uid);

} else
	echo RenderClosedCompetitions();

echo Analytics::Page("winners.php");
?>