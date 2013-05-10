<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/

require_once(dirname(__FILE__)."/../constants.php");
require_once(dirname(__FILE__)."/../settings.php");
require_once(dirname(__FILE__)."/email.php");

class Log {

	/*
	 * Appends an error message to a log file, check settings.php for the log rotation settings
	 * The logging is skipped if the current log level is higher than the level of the error
	 */
	private static function write($level, $classname, $message) {
		global $CURRENT_LOG_LEVEL;
		global $LOG_LEVEL;
		global $LOG_FILE;
		global $LOG_TIME_FORMAT;
		global $LOG_FILE_PATH;
		
		if ($CURRENT_LOG_LEVEL <= $LOG_LEVEL[$level]) {
			if (!file_exists($LOG_FILE[$classname])) {
				$fp = fopen($LOG_FILE[$classname], "w+");
				fclose($fp);
				chmod($LOG_FILE[$classname], 0666);
			}
			$fp = fopen($LOG_FILE[$classname], "a+");
			fwrite($fp, date($LOG_TIME_FORMAT)." ".$level." ".$message."\n");
			fclose($fp);
		}
	}
	
	public static function trace($classname, $message) {
		Log::write("TRACE", $classname, $message);
	}
	
	public static function debug($classname, $message) {
		Log::write("DEBUG", $classname, $message);
	}
	
	public static function info($classname, $message) {
		Log::write("INFO", $classname, $message);
	}
	
	public static function error($classname, $message) {
		Log::write("ERROR", $classname, $message);
	}

	/*
	 * If an error is critical (eg. cache or database down), we email the sysadmins. 
	 * Potential improvement: use the twitter API so that they receive it as a text message
	 */	
	public static function critical($classname, $message) {
		global $ADMIN_EMAIL;
		global $EMAIL_SUBJECT;
		Log::write("CRITICAL", $classname, $message);
		foreach ($ADMIN_EMAIL as $email) {
			try {
				Email::mail($email, "CRITICAL_ERROR", array("error" => $message));
			} catch (EmailException $e) {
				Log::error(__CLASS__, "Critical error email could not be sent to $email");
			}
		}
	}
}

?>