<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/

require_once(dirname(__FILE__)."/../constants.php");

$random_hash = md5(date("r", time())); 

$EMAIL_SUBJECT["CRITICAL_ERROR"] = "Critical error on battlebox.tv";
$EMAIL_HEADERS["CRITICAL_ERROR"]  = "From: BattleBox.tv <no-reply@battlebox.tv>\r\nReply-To: no-reply@battlebox.tv";
$EMAIL_HEADERS["CRITICAL_ERROR"] .= "\r\nContent-Type: multipart/alternative; boundary=\"PHP-alt-".$random_hash."\"";

$EMAIL_BODY["CRITICAL_ERROR"]  = "--PHP-alt-$random_hash\r\n";
$EMAIL_BODY["CRITICAL_ERROR"] .= "Content-Type: text/plain; charset=\"iso-8859-1\"\r\n";
$EMAIL_BODY["CRITICAL_ERROR"] .= "Content-Transfer-Encoding: 7bit\r\n";
$EMAIL_BODY["CRITICAL_ERROR"] .= "\r\n";
$EMAIL_BODY["CRITICAL_ERROR"] .= "A critical error occured on battlebox.tv:\r\n";
$EMAIL_BODY["CRITICAL_ERROR"] .= "\r\n";
$EMAIL_BODY["CRITICAL_ERROR"] .= "#error\r\n";
$EMAIL_BODY["CRITICAL_ERROR"] .= "--PHP-alt-$random_hash\r\n";
$EMAIL_BODY["CRITICAL_ERROR"] .= "Content-Type: text/html; charset=\"iso-8859-1\"\r\n";
$EMAIL_BODY["CRITICAL_ERROR"] .= "Content-Transfer-Encoding: 7bit\r\n";
$EMAIL_BODY["CRITICAL_ERROR"] .= "\r\n";
$EMAIL_BODY["CRITICAL_ERROR"] .= "<h2>A critical error occured on battlebox.tv:<br/>\r\n";
$EMAIL_BODY["CRITICAL_ERROR"] .= "<br/>\r\n";
$EMAIL_BODY["CRITICAL_ERROR"] .= "#error\r\n";
$EMAIL_BODY["CRITICAL_ERROR"] .= "--PHP-alt-$random_hash--";

?>
