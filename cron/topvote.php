#!/usr/local/bin/php
<?php


/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/../entities/competition.php');
require_once (dirname(__FILE__) . '/../entities/topic.php');

$topics = Topic :: getTopicList();

if (empty ($topics))
	echo "No topic to pick from today";
else {
	asort($topics);

	foreach ($topics as $topic_id => $score)
		$topics[$topic_id] = $topic_id;

	$topic_id = array_pop($topics);
	Competition :: createCompetition($topic_id);

	echo "The new competition is based on the topic with id=" . $topic_id;
}
?>