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
	echo "No topic to cleanup";
else {
	arsort($topics);

	foreach ($topics as $topic_id => $score) {
		if ($score <= -10) {
			Topic :: deleteTopic($topic_id);
			echo "Deleted topic ".$topic_id.", score was ".$score."\n";
		}
	}
}
?>