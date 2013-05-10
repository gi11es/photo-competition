<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/

require_once(dirname(__FILE__)."/../templates/email.php");
require_once(dirname(__FILE__)."/template.php");

 class EmailException extends Exception {}
 
 class Email {
 
 	/*
 	 * Send an email message based on an email template
 	 * template_values contains a hashmap of the template values to be replaced
 	 * This function raises an exception if the email wasn"t sent succesfully
 	 */
 	public static function mail($destination, $template_name, $template_values) {
 		global $EMAIL_SUBJECT;
 		global $EMAIL_HEADERS;
 		global $EMAIL_BODY;
 		
 		if (!mail($destination, 
	 				$EMAIL_SUBJECT[$template_name], 
	 				Template::Templatize($EMAIL_BODY[$template_name], $template_values), 
	 				$EMAIL_HEADERS[$template_name]
	 		))
	 		throw new EmailExcepion("Email to $destination using template $template_name failed to be sent");
 	}
 }
?>
