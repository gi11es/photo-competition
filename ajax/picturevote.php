<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/
        
require_once (dirname(__FILE__) . '/../includes/facebook.php');
require_once (dirname(__FILE__) . '/../entities/picturevote.php');
        
$facebook = new Facebook($api_key, $secret);
$uid = $facebook->require_login();

if (isset($_REQUEST["picture_id"]) && isset($_REQUEST["topic_id"]) && isset($_REQUEST["value"])) {
        $value =$_REQUEST["value"];
        if ($value < 0)
                $value = 1;
        PictureVote :: setPictureVote($_REQUEST["picture_id"], $_REQUEST["topic_id"], $uid, $value);
        echo "<fb:success message=\"Thanks for voting!\"/>";
} else echo "<fb:error message=\"An error occured during your vote, please refresh the page and retry\"/>";
                
?>
