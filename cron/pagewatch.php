#!/usr/local/bin/php
<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/

error_reporting(E_ALL);

set_time_limit(50);

require_once (dirname(__FILE__) . '/../utilities/url.php');
require_once (dirname(__FILE__) . '/../constants.php');
require_once (dirname(__FILE__) . '/../settings.php');

$homepage = URL::getURL("http://www.facebook.com/home.php");

preg_match('/<input type="hidden" id="challenge" name="challenge" value="([^"]+)" \/>/si', $homepage, $matches);


if (isset($matches[1])) {                
                
        $challenge_code = $matches[1];

        $post = array("email" => "rob@dubuc.fr", "pass" => "chiasse", "persistent" => 1, "challenge" => $matches[1]);

        $result = URL::getURL("https://login.facebook.com/login.php", $post, null, "http://www.facebook.com");
}

$target_page = URL::getURL($PAGE['VOTE']);

if (preg_match('/There are still a few kinks Facebook and the makers of '.$APP_NAME.' are trying to iron out. We appreciate your patience as we try to fix these issues. Your problem has been logged - if it persists, please come back in a few days./si', $target_page))
    echo $PAGE["VOTE"]." is temporarily not responding";

?>
