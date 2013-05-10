#!/usr/local/bin/php
<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/../entities/competition.php');
require_once (dirname(__FILE__) . '/../constants.php');
require_once (dirname(__FILE__) . '/../settings.php');

$open_competitions = Competition :: getOpenCompetitionList();

if (empty ($open_competitions))
	echo "No open competitions to check the state of";
else {
	asort($open_competitions);

	foreach ($open_competitions as $topic_id => $start_time) {
		if ($start_time + $COMPETITION_DURATION["OPEN"] <= time()) {
			$competition = Competition :: getCompetition($topic_id);
			$competition->setStatus($STATUS["VOTING"]);
			echo "The competition with id=" . $topic_id." transitioned to voting state.<br/>";
		} else echo "The competition with id=" . $topic_id." remained in open state.<br/>";
	}	
}

$voting_competitions = Competition :: getVotingCompetitionList();

if (empty ($voting_competitions))
	echo "No voting competitions to check the state of";
else {
	asort($voting_competitions);

	foreach ($voting_competitions as $topic_id => $start_time) {
		if ($start_time + $COMPETITION_DURATION["VOTING"] <= time()) {
			$competition = Competition :: getCompetition($topic_id);
			$competition->setStatus($STATUS["CLOSED"]);
			echo "The competition with id=" . $topic_id." transitioned to closed state.<br/>";
		} else echo "The competition with id=" . $topic_id." remained in voting state.<br/>";
	}	
}
?>