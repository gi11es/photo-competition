<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/

require_once (dirname(__FILE__).'/../utilities/cache.php');
require_once (dirname(__FILE__).'/../utilities/db.php');
require_once (dirname(__FILE__).'/../utilities/log.php');
require_once (dirname(__FILE__).'/../constants.php');
require_once (dirname(__FILE__).'/../settings.php');

class User {
	private $id;
	private $session_key = "";
	private $saved_friends_ids = Array();
	private $saved_friends_timestamp = null;
	private $status = 0;
	
	private static $statements_started = false;
	private static $statement_getUser;
	private static $statement_getUserList;
	private static $statement_createUser;
	private static $statement_setSessionKey;
	private static $statement_setStatus;
	private static $statement_delete;

	public static function getUser($user_id) {
		global $TABLE;
		global $COLUMN;
		Log::trace(__CLASS__, "retrieving user with id=".$user_id);

		// First try to retrieve the user from the cache
		try {
			$ibuser = Cache::get("User-".$user_id);
			Log::trace(__CLASS__, "Found in the cache user with id=".$user_id);
			return $ibuser;
		} catch (CacheException $e) { // If that fails, get the user from the DB
			Log::trace(__CLASS__, "Can't find user in the cache, looking in the DB for user with id=".$user_id);
			if (!User::$statements_started) User::prepareStatements();
			Log :: trace("DB", "Executing statement_getUser");
			$result = User::$statement_getUser->execute($user_id);	

			if (!$result || PEAR::isError($result) || $result->numRows() != 1) {
				$ibuser = false;
			} else {
				$row = $result->fetchRow();
				$ibuser = new User();
				$ibuser->setId($row[$COLUMN["USER_ID"]]);
				$ibuser->setSessionKey($row[$COLUMN["SESSION_KEY"]], false);
				$ibuser->setStatus($row[$COLUMN["STATUS"]], false);
				
				$result->free();
			}
		}

		// We couldn't find that user id in the database or in the cache, let's create the user entry
		if (!$ibuser) {
			Log::trace(__CLASS__, "Can't be found in cache or DB, must create user with id=".$user_id);
			$ibuser = new User();
			$ibuser->setId($user_id);

			Log :: trace("DB", "Executing statement_createUser");
			User::$statement_createUser->execute($ibuser->getId());
			try {
				Cache::set("User-".$ibuser->getId(), $ibuser);
			} catch (CacheException $ex) {
				Log::error(__CLASS__, $ex->getMessage());
			}
			
			return $ibuser;
		} else {
			// Since we just fetched the user from the DB, let's put him/her in the cache
			try {
				Cache::set("User-".$ibuser->getId(), $ibuser);
			} catch (CacheException $ex) {
				Log::error(__CLASS__, $ex->getMessage());
			}
			return $ibuser;
		}
	}
	
	public static function getUserList() {
		global $COLUMN;
		
		if (!User::$statements_started) User::prepareStatements();		
		$list = Array();
		
		Log :: trace("DB", "Executing statement_getUserList");
		$result = User::$statement_getUserList->execute(1);
		while ($row = $result->fetchRow()) {
			$list []= $row[$COLUMN["USER_ID"]];
		}
		$result->free();
		return $list;
	}
	
	public function getFriendsIDs($api_client) {
		if ($this->saved_friends_timestamp == null || time() > ($this->saved_friends_timestamp + 900)) { // 15 minutes
		
			$results = Array();
			$fql_result = $api_client->fql_query("SELECT uid FROM user WHERE has_added_app = 1 AND uid IN (SELECT uid2 FROM friend WHERE uid1 = ".$this->id.")");
		
			if ($fql_result)
			foreach($fql_result as $result)
				$results []= $result["uid"];
				
			$this->saved_friends_ids = $results;
			$this->saved_friends_timestamp = time();
			$this->saveCache();
			
			return $results;
		} else return $this->saved_friends_ids;
	}
	
	public static function deleteUser($user_id) {
		try {
			Cache::delete("User-".$user_id);
		} catch (CacheException $ex) {
			Log::error(__CLASS__, $ex->getMessage());
		}
		
		if (!User::$statements_started) User::prepareStatements();
	
		Log::trace(__CLASS__, "deleting user with id=".$user_id);
			
		Log :: trace("DB", "Executing User::statement_delete");
		$result = User::$statement_delete->execute($user_id);	

		if ($result != 1) {
			Log::error(__CLASS__, "Could not delete user entry for user_id=".$user_id);
		}
	}

	public function saveCache() {
		Log::trace(__CLASS__, "updating cache entry of user with id=".$this->id);
		try {
			Cache::replace("User-".$this->id, $this);
		} catch (CacheException $ex) {
			Log::error(__CLASS__, $ex->getMessage());
		}
	}

	public function getId() {
		return $this->id;
	}

	public function setId($id) {
		$this->id = $id;
	}
	
	public function getSessionKey() {
		return $this->session_key;
	}

	public function setSessionKey($newkey, $persist=true) {	
		$this->session_key = $newkey;
		if ($persist) {
			$this->saveCache();
			if (!User::$statements_started) User::prepareStatements();
			Log :: trace("DB", "Executing statement_setSessionKey");
			User::$statement_setSessionKey->execute(array($this->session_key, $this->id));	
		}
	}
	
	public function getStatus() {
		return $this->status;
	}

	public function setStatus($newstatus, $persist=true) {	
		$this->status = $newstatus;
		if ($persist) {
			$this->saveCache();
			if (!User::$statements_started) User::prepareStatements();
			Log :: trace("DB", "Executing User::statement_setStatus");
			User::$statement_setStatus->execute(array($this->status, $this->id));	
		}
	}
	
	public function hasAddedApp($api_client) {
		$fql_result = $api_client->fql_query("SELECT has_added_app FROM user WHERE uid = ".$this->id);
		
		if (isset($fql_result[0])) {
			return ($fql_result[0]["has_added_app"] == 1);
		} else return false;
	}
	
	public function getApps() {
		$allapps = App::getAppList();
		$apps = array();
		$result = array();
		
		foreach ($allapps as $appid => $user_id) {
			if ($user_id == $this->id) $apps []= $appid;
		}
		
		foreach ($apps as $appid)
			$result []= App::getApp($appid);
			
		return $result;
	}
	
	public static function prepareStatements() {
		global $TABLE;
		global $COLUMN;
		global $DATABASE;
		global $COLUMN_TYPE;
		
		Log::trace(__CLASS__, "Preparing DB statements for this class");
		
		User::$statement_getUser = DB::prepareRead( 
				"SELECT ".$COLUMN["USER_ID"].", ".$COLUMN["SESSION_KEY"].", ".$COLUMN["STATUS"]
				." FROM ".$DATABASE["PREFIX"].$TABLE["USER"]
				." WHERE ".$COLUMN["USER_ID"]." = ?"
						, array('text'));
						
		User::$statement_getUserList = DB::prepareRead( 
				"SELECT ".$COLUMN["USER_ID"]." FROM ".$DATABASE["PREFIX"].$TABLE["USER"]." WHERE ?"
						, array('integer'));
		
		User::$statement_createUser = DB::prepareWrite( 
				"INSERT INTO ".$DATABASE["PREFIX"].$TABLE["USER"]." (".$COLUMN["USER_ID"].") VALUES(?)"
						, array('text'));

		User::$statement_setSessionKey = DB::prepareWrite( 
				"UPDATE ".$DATABASE["PREFIX"].$TABLE["USER"]." SET ".$COLUMN["SESSION_KEY"]." = ? WHERE ".$COLUMN["USER_ID"]." = ?"
						, array('text', 'text'));
						
		User::$statement_setStatus = DB::prepareWrite( 
				"UPDATE ".$DATABASE["PREFIX"].$TABLE["USER"]." SET ".$COLUMN["STATUS"]." = ? WHERE ".$COLUMN["USER_ID"]." = ?"
						, array('integer', 'text'));
						
		User::$statement_delete = DB::prepareWrite( 
				"DELETE FROM ".$DATABASE["PREFIX"].$TABLE["USER"]." WHERE ".$COLUMN["USER_ID"]." = ?"
						, array('text'));
		
		User::$statements_started = true;
	}
	
	public function isFriend($facebook, $friend_id) {
		$renew = false;
		$isfriend = false;
		try {
			$isfriend = Cache::get("IsFriend-".$this->id."-".$friend_id);
			// When was the friendship last checked
			$isfriend_timestamp = Cache::get("IsFriend-".$this->id."-".$friend_id."-timestamp");
			if (time() - $isfriend_timestamp > 86400) $renew = true; // If the value was checked more than 24 hours ago, we check again
		} catch (CacheException $e) {
			$renew = true;
		}
		
		if ($renew) {
			try {
				$session_key = $this->getSessionKey();
				$facebook->set_user($this->id, $session_key);
					$result = $facebook->api_client->friends_areFriends(array (
						$this->id
					), array (
						$friend_id
					));
					if (isset ($result[0]['are_friends']) && $result[0]['are_friends'] == 1) $isfriend = true;
			} catch (Exception $e) { $isfriend = false; }
			try {
				Cache::set("IsFriend-".$this->id."-".$friend_id, $isfriend);
				Cache::set("IsFriend-".$this->id."-".$friend_id."-timestamp", time());
			} catch (CacheException $e) {}
		}
		
		return $isfriend;
	}
}

?>