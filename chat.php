<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/

error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');

$start_time = microtime(true);

require_once (dirname(__FILE__).'/includes/facebook.php');
require_once(dirname(__FILE__).'/entities/user.php');
require_once(dirname(__FILE__).'/utilities/analytics.php');
require_once(dirname(__FILE__).'/utilities/ui.php');
require_once(dirname(__FILE__).'/constants.php');
require_once(dirname(__FILE__).'/settings.php');

include($STYLING["TEXT"]);
include($STYLING["TOPMENU"]);

$facebook = new Facebook($api_key, $secret);
$uid = $facebook->require_login();

$user = User :: getUser($uid);

if ($user->getStatus() == $STATUS["BANNED"]) {
	echo "<fb:redirect url=\"".$PAGE["BANNED"]."\"/>";
	exit(0);
}

echo UI::RenderMenu($PAGE_CODE["CHAT"], $uid);

if (isset($ADMINS[$uid])) {
	$candelete = true;
	$canmark = true;
} else {
	$candelete = false;
	$canmark = false;
}

?>


<fb:board xid="<?=$BOARD_NAME?>" canpost="true" candelete="<?=$candelete?>" canmark="<?=$canmark?>" cancreatetopic="true" numtopics="10" returnurl="<?=$PAGE["CHAT"]?>">  <fb:title><?=$TEXT["FORUM"]?></fb:title> </fb:board>

<?php
echo Analytics::Page("chat.php");
?>