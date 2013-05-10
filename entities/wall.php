<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/

require_once (dirname(__FILE__) . '/../utilities/cache.php');
require_once (dirname(__FILE__) . '/../utilities/db.php');
require_once (dirname(__FILE__) . '/../utilities/log.php');
require_once (dirname(__FILE__) . '/../constants.php');
require_once (dirname(__FILE__) . '/../settings.php');
require_once (dirname(__FILE__) . '/picture.php');
require_once (dirname(__FILE__) . '/topic.php');
require_once (dirname(__FILE__) . '/topicvote.php');

class WallException extends Exception {
}

class Wall {
	private $picture_id;
	private $topic_id;
	private $posts = array ();

	private static $statements_started = false;
	private static $statement_getWall;
	private static $statement_addPost;
	private static $statement_deleteLastPost;

	public static function processWallPost($facebook, $uid, $notify = true) {
		global $_REQUEST;
		global $PAGE;

		// The user posted a wall entry, let's add it to the wall
		if (isset ($_REQUEST["wall_text"]) && isset($_REQUEST["picture"]) && isset($_REQUEST["topic"])) {
			$picture = Picture :: getPicture($_REQUEST["picture"], $_REQUEST["topic"]);
			$topic = Topic :: getTopic($_REQUEST["topic"]);
			
			$wall = Wall :: getWall($_REQUEST["picture"], $_REQUEST["topic"]);
			$wall->addPost($uid, htmlentities($_REQUEST["wall_text"]));

			if ($uid != $picture->getUserId()) {
				$user = User :: getUser($picture->getUserId());
				$session_key = $user->getSessionKey();
				$facebook->set_user($picture->getUserId(), $session_key);
				try {
					$facebook->api_client->notifications_send($picture->getUserId(), "-> <fb:name ifcantsee=\"Anonymous user\" uid=\"" . $uid . "\"/> wrote a comment on your <a href=\"" . $PAGE["YOUR_SUBMISSIONS"] . "?topic=" . $_REQUEST["topic"] . "&picture=" . $_REQUEST["picture"] . "\">" . $topic->getTitle() . " entry</a>", "user_to_user");
				} catch (Exception $e) {
				}

				$user = User :: getUser($uid);
				$session_key = $user->getSessionKey();
				$facebook->set_user($uid, $session_key);
			}
		}
		
		if (isset ($_REQUEST["delete_post"]) && isset($_REQUEST["picture"]) && isset($_REQUEST["topic"]) && isset($_REQUEST["post_user_id"])) {
			// Sanity check: is the user trying to delete the post its author?
			if (strcmp($_REQUEST["post_user_id"], $uid) == 0) {
				if (!Wall :: $statements_started)
					Wall :: prepareStatements();
					
				Log :: trace("DB", "Executing statement_deleteLastPost");
				Wall :: $statement_deleteLastPost->execute(array (
				$_REQUEST["picture"],
				$_REQUEST["topic"],
				$_REQUEST["post_user_id"]
			));
			
			try {
				Cache :: delete("Wall-" . $_REQUEST["picture"] . "-" . $_REQUEST["topic"]);
			} catch (CacheException $ex) {
				Log :: error(__CLASS__, $ex->getMessage());
			}
			}
		}
	}

	public static function getWall($picture_id, $topic_id) {
		global $TABLE;
		global $COLUMN;

		if (!Wall :: $statements_started)
			Wall :: prepareStatements();

		Log :: trace(__CLASS__, "retrieving wall with picture_id=" . $picture_id . " topic_id=" . $topic_id);

		// First try to retrieve the wall from the cache
		try {
			$wall = Cache :: get("Wall-" . $picture_id . "-" . $topic_id);
			Log :: trace(__CLASS__, "Found in the cache wall with picture_id=" . $picture_id . " topic_id=" . $topic_id);
			return $wall;
		} catch (CacheException $e) { // If that fails, get the wall from the DB
			Log :: trace(__CLASS__, "Can't find wall in the cache, looking in the DB for wall with picture_id=" . $picture_id . " topic_id=" . $topic_id);
			Log :: trace("DB", "Executing statement_getWall");
			$result = Wall :: $statement_getWall->execute(array (
				$picture_id,
				$topic_id
			));

			if (!$result || PEAR :: isError($result)) {
				$wall = false;
			} else {
				$wall = new Wall();
				$wall->picture_id = $picture_id;
				$wall->topic_id = $topic_id;
				while ($row = $result->fetchRow()) {
					$wall->addPost($row[$COLUMN["USER_ID"]], $row[$COLUMN["TEXT"]], strtotime($row[$COLUMN["POST_TIME"]] . " GMT"), false);
				}
				$result->free();
			}

		}

		// We couldn't find that wall in the database nor in the cache
		if (!$wall) {
			throw new WallException("The wall couldn't be found in the cache or the DB with picture_id=" . $picture_id . " topic_id=" . $topic_id);
		} else {
			// Since we just fetched the wall from the DB, let's put it in the cache
			try {
				Cache :: set("Wall-" . $picture_id . "-" . $topic_id, $wall);
			} catch (CacheException $ex) {
				Log :: error(__CLASS__, $ex->getMessage());
			}
			return $wall;
		}
	}

	public function getPosts() {
		return $this->posts;
	}

	public function addPost($user_id, $text, $timestamp = false, $persist = true) {
		if (!$timestamp) {
			$time = time() + 2 * 60 * 60;
		} else
			$time = $timestamp;
		$this->posts[] = array (
			"user_id" => $user_id,
			"text" => $text,
			"post_time" => $time
		);

		if ($persist) {
			if (!Wall :: $statements_started)
				Wall :: prepareStatements();
			//$oldzone = date_default_timezone_get();
			//date_default_timezone_set("GMT");
			Log :: trace("DB", "Executing statement_addPost");
			Wall :: $statement_addPost->execute(array (
				$this->picture_id,
				$this->topic_id,
				$user_id,
				$text,
				gmdate('Y-m-d H:i:s',
				$time
			)));
			//date_default_timezone_set($oldzone);

			try {
				Cache :: delete("Wall-" . $this->picture_id . "-" . $this->topic_id);
			} catch (CacheException $ex) {
				Log :: error(__CLASS__, $ex->getMessage());
			}
		}
	}

	public static function prepareStatements() {
		global $TABLE;
		global $COLUMN;
		global $DATABASE;
		global $COLUMN_TYPE;
		global $STATUS;

		Log :: trace(__CLASS__, "Preparing DB statements for this class");

		Wall :: $statement_getWall = DB :: prepareRead("SELECT " .
		$COLUMN["USER_ID"] . ", " . $COLUMN["TEXT"] . ", " . $COLUMN["POST_TIME"] .
		" FROM " . $DATABASE["PREFIX"] . $TABLE["WALL"] .
		" WHERE " . $COLUMN["PICTURE_ID"] . " = ? AND " . $COLUMN["TOPIC_ID"] . " = ?", array (
			'text',
			'integer'
		));

		Wall :: $statement_addPost = DB :: prepareWrite("INSERT INTO " .
		$DATABASE["PREFIX"] . $TABLE["WALL"] . " (" . $COLUMN["PICTURE_ID"] . ", " . $COLUMN["TOPIC_ID"] . ", " . $COLUMN["USER_ID"] . ", " . $COLUMN["TEXT"] . ", " . $COLUMN["POST_TIME"] . ") VALUES(?, ?, ?, ?, ?)", array (
			'text',
			'integer',
			'text',
			'text',
			'timestamp'
		));
		
		Wall :: $statement_deleteLastPost = DB :: prepareWrite("DELETE FROM " .
		$DATABASE["PREFIX"] . $TABLE["WALL"] . " WHERE " . $COLUMN["PICTURE_ID"] . " = ? AND " . $COLUMN["TOPIC_ID"] . " = ? AND " . $COLUMN["USER_ID"] . " = ? ORDER BY " . $COLUMN["POST_TIME"] . " DESC LIMIT 1", array (
			'text',
			'integer',
			'text'
		));

		Wall :: $statements_started = true;
	}
}
?>