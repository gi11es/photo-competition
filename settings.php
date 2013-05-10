<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/

$APP_PATH = "http://apps.facebook.com/photographycomp/";
$APP_REAL_PATH = "http://legacy.inspi.re/photocomp/";
$APP_LOCAL_PATH = dirname(__FILE__);
$APP_NAME = "Photography Competition";

require_once (dirname(__FILE__).'/constants.php');

$api_key = '52b21f4e07a72dd2a5dab8f6440e4d6f';
$secret  = '509111b2680cbe04aabbb0af631ad97e';

$APP_ABOUT_PAGE = "http://www.facebook.com/apps/application.php?api_key=".$api_key;
$APP_ADD_PAGE = "http://www.facebook.com/add.php?api_key=".$api_key;
$APP_REVIEW_PAGE = "http://www.facebook.com/apps/application.php?id=6118650948#a_6261817190";

$ADMINS = array(500347728 => true);

$WEEKLY_LIMIT = 1;

$MEMCACHE[0]["HOST"] = 'localhost';
$MEMCACHE[0]["PORT"] = 11211;
$MEMCACHE_PREFIX = 'PhotoComp-'; // DO NOT MODIFY!! 

$CURRENT_LOG_LEVEL = $LOG_LEVEL["ERROR"];
$LOG_TIME_FORMAT = "Y-m-d H:i:s";

$LOG_FILE_PATH = "/home/legacy/logs/photocomp/";
$UPLOAD_PATH = "/home/legacy/uploads/photocomp";
$LOG_FILE["Cache"] = $LOG_FILE_PATH."Cache-".date("Y-m-d").".log";
$LOG_FILE["DB"] = $LOG_FILE_PATH."DB-".date("Y-m-d").".log";
$LOG_FILE["URL"] = $LOG_FILE_PATH."URL-".date("Y-m-d").".log";
$LOG_FILE["Topic"] = $LOG_FILE_PATH."Topic-".date("Y-m-d").".log";
$LOG_FILE["TopicVote"] = $LOG_FILE_PATH."TopicVote-".date("Y-m-d").".log";
$LOG_FILE["User"] = $LOG_FILE_PATH."User-".date("Y-m-d").".log";
$LOG_FILE["Competition"] = $LOG_FILE_PATH."Competition-".date("Y-m-d").".log";
$LOG_FILE["Picture"] = $LOG_FILE_PATH."Picture-".date("Y-m-d").".log";
$LOG_FILE["PictureVote"] = $LOG_FILE_PATH."PictureVote-".date("Y-m-d").".log";
$LOG_FILE["Wall"] = $LOG_FILE_PATH."Wall-".date("Y-m-d").".log";

$DATABASE["HOST"] = "localhost";
$DATABASE["USER"] = "legacy";
$DATABASE["PASSWORD"] = "roumb4l4";
$DATABASE["NAME"] = "legacy";
$DATABASE["PREFIX"] = "photocomp_";

$INSTALL_URL = "http://www.facebook.com/apps/application.php?api_key=".$api_key;

$COMPETITION_DURATION["OPEN"] = 345600; // 4 days
$COMPETITION_DURATION["VOTING"] = 518400; // 2 days ( = 4 + 2)

$TEXT["FORUM"] = "General discussion - talk about photography, exchange tips and techniques!";
$TEXT["USER"] = "Photographer";
$TEXT["ITEM"] = "photo";
$TEXT["CRAFT"] = "photography";

$BOARD_NAME = "photocompetition_board";
$COOKIE_FILE = "/home/legacy/logs/photocomp/cookie.txt";
$GOOGLE_ANALYTICS_CODE = "UA-60164-18";

$ADMIN_EMAIL = array("kouiskas@gmail.com");

$TOPICS_CLOSED = true;

?>