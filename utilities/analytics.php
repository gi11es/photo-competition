<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/

require_once(dirname(__FILE__).'/../settings.php');

class Analytics {
	
	public static function Page($page) {
		global $GOOGLE_ANALYTICS_CODE;
	
		$result = '<fb:google-analytics uacct="'.$GOOGLE_ANALYTICS_CODE.'" page="'.$page.'" />';
		
		return $result;
	}
}

?>