<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/

require_once(dirname(__FILE__)."/../settings.php");
require_once(dirname(__FILE__)."/log.php");

class URL {

	private static $ch = null;
	private static $user_agent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6";
	private static $timeout = 30;

	// Cleanup function, lets us close the curl session
	public static function shutdown() {
		Log::trace(__CLASS__, "*** stopping ***");
    	curl_close(URL::$ch);
	}
	
	// Clear the cookies if we need to reset the "browser" session
	public static function clearCookies() {
		global $COOKIE_FILE;
	
		unlink($COOKIE_FILE);
	}
	
	private static function checkInit() {
		if (URL::$ch == null) {
			Log::trace(__CLASS__, "*** starting ***");
			URL::$ch = curl_init();
			register_shutdown_function(array("URL", "shutdown"));
		}
	}

	/*
	 * Returns the contents of a given URL
	 * $request containg the URL request, along with urlencoded get parameters
	 * $post contains optional post parameters in a hashmap
	 * $authstring is used for BASIC authentication
	 * $referer specifies a fake referer to be used in the request
	 */ 
	public static function getURL($request, $post=null, $authstring=null, $referer=null) {
		global $COOKIE_FILE;
	
		URL::checkInit();
		Log::trace(__CLASS__, "getURL ".$request);
		
		curl_setopt(URL::$ch, CURLOPT_URL, $request); // set url to post to
		curl_setopt(URL::$ch, CURLOPT_FAILONERROR, 1);              // Fail on errors
		curl_setopt(URL::$ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
		curl_setopt(URL::$ch, CURLOPT_TIMEOUT, URL::$timeout); // times out after 15s
		curl_setopt(URL::$ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		
		if ($referer != null)
			curl_setopt(URL::$ch, CURLOPT_REFERER, $referer);
		
		// If a cookie file is specified in settings.php we use it to store the cookies
		if ($COOKIE_FILE != null) {
			curl_setopt(URL::$ch, CURLOPT_COOKIEJAR, $COOKIE_FILE);
			curl_setopt(URL::$ch, CURLOPT_COOKIEFILE, $COOKIE_FILE);
		}
		
		if ($post != null) {
			curl_setopt(URL::$ch, CURLOPT_POST, true);
			curl_setopt(URL::$ch, CURLOPT_POSTFIELDS, $post);
		}
		
		if ($authstring != null) {
			curl_setopt(URL::$ch, CURLOPT_HTTPHEADER, array("Authorization: Basic ".base64_encode($authstring)));
		}

		curl_setopt(URL::$ch, CURLOPT_USERAGENT, URL::$user_agent);

		return curl_exec(URL::$ch);
	}
}

?>