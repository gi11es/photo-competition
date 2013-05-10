<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/

require_once (dirname(__FILE__) . '/includes/facebook.php');
require_once (dirname(__FILE__) . '/utilities/ui.php');
require_once (dirname(__FILE__) . '/constants.php');
require_once (dirname(__FILE__) . '/settings.php');

include ($STYLING["TEXT"]);
include ($STYLING["TOPMENU"]);

$facebook = new Facebook($api_key, $secret);
$uid = $facebook->require_login();

echo UI :: RenderMenu(-1, $uid);

?>

<br/>
<div class="basictextblock"><h1>You've been banned from <?=$APP_NAME?>!</h1>
<br/>
One of the following reasons was the cause:<br/>
<br/>
- You've cheated by creating a fake Facebook account and voting on your own entries<br/>
- You've asked friends to vote for your entry<br/>
- You've given very high scores to a friend's entry systematically<br/>
- You've tried to hack the application by entering forged URLs<br/>
- You've posted spam on the application<br/>
- You've had an inappropriate verbal behavior<br/>
<br/>
People like you are not welcome in the <?=$APP_NAME?> community. Feel free to uninstall the application, as you will not regain access to it.<br/></div><br/>
