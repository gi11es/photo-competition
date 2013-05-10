<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/

// This script was used to check if any sympathy voting was happening, the results were appaling

error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');

$start_time = microtime(true);

require_once (dirname(__FILE__) . '/../includes/facebook.php');
require_once (dirname(__FILE__) . '/../entities/competition.php');
require_once (dirname(__FILE__) . '/../entities/picture.php');
require_once (dirname(__FILE__) . '/../entities/picturevote.php');
require_once (dirname(__FILE__) . '/../entities/user.php');
require_once (dirname(__FILE__) . '/../utilities/UI.php');
require_once (dirname(__FILE__) . '/../constants.php');
require_once (dirname(__FILE__) . '/../settings.php');

$facebook = new Facebook($api_key, $secret);

$closed_competitions = Competition :: getClosedCompetitionList();

$vote_counter = 0;
$friends_counter = 0;
$friends_total = 0;
$non_friend_counter = 0;
$non_friend_total = 0;
$failed = 0;
$competition = 0;
$user_stats = array();
foreach ($closed_competitions as $topic_id => $whatever) {
	$competition++;
	if ($competition > 49 && $competition < 52) {
		$pictures = Picture :: getPictureTopicList($topic_id);
		$votes = array ();
		foreach ($pictures as $user_id => $picture_id) {
			$user = User :: getUser($user_id);
			$session_key = $user->getSessionKey();
			$facebook->set_user($user_id, $session_key);

			foreach (PictureVote :: getPictureVoteList($picture_id, $topic_id) as $voter_id => $vote) {
				$vote_counter++;
				try {
					$result = $facebook->api_client->friends_areFriends(array (
						$user_id
					), array (
						$voter_id
					));
					if (isset ($result[0]['are_friends']) && $result[0]['are_friends'] == 1) {
						$friends_counter++;
						$friends_total+=$vote;
						if (isset($user_stats[$user_id]["friends"]))
						$user_stats[$user_id]["friends"] ++;
						else
						$user_stats[$user_id]["friends"] = 1;
					}
					else {
						$non_friend_counter++;
						$non_friend_total+=$vote;
						if (isset($user_stats[$user_id]["non_friends"]))
						$user_stats[$user_id]["non_friends"] ++;
						else
						$user_stats[$user_id]["non_friends"] = 1;
					}
				} catch (Exception $e) {
					$failed++;
				}
			}
		}
	}
}

echo $vote_counter . " votes were cast in total.<br/>";
echo $friends_counter . " of these were between facebook friends.<br/>";
echo $failed . " failures when trying to check friendship.<br/>";
echo "The average vote between facebook friends was ".($friends_total/$friends_counter)."<br/>";
echo "The average vote between non-facebook friends was ".($non_friend_total/$non_friend_counter)."<br/>";

foreach ($user_stats as $user_id=> $stat) {
	if (isset($stat["friends"]) && isset($stat["non_friends"])) $user_ratio[$user_id] = $stat["friends"] / $stat["non_friends"];
	elseif (isset($stat["friends"])) $user_ratio[$user_id] = 1;
}

arsort($user_ratio);
print_r($user_ratio);
?>
