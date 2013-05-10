<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/

require_once (dirname(__FILE__).'/../includes/facebook.php');
require_once(dirname(__FILE__)."/../utilities/cache.php");
require_once(dirname(__FILE__).'/../utilities/ui.php');
require_once(dirname(__FILE__)."/../settings.php");

error_reporting(E_ALL);

$facebook = new Facebook($api_key, $secret);
$uid = $facebook->require_login();

echo UI::RenderMenu(null, $uid);

if (isset($ADMINS[$uid])) {
	Cache::flush();
	echo "Cache flushed successfully";
}



?>
