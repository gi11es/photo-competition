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
require_once (dirname(__FILE__) . '/topicvote.php');

class TopicException extends Exception {
}

class Topic {
	private $id;
	private $user_id;
	private $title;
	private $description;
	private $status;

	private static $statements_started = false;
	private static $statement_getTopic;
	private static $statement_getTopicList;
	private static $statement_getListByUserId;
	private static $statement_createTopic;
	private static $statement_deleteTopic;
	private static $statement_setStatus;

	public static function getTopic($topic_id) {
		global $TABLE;
		global $COLUMN;

		Log :: trace(__CLASS__, "retrieving topic with id=" . $topic_id);

		// First try to retrieve the topic from the cache
		try {
			$topic = Cache :: get("Topic-" . $topic_id);
			Log :: trace(__CLASS__, "Found in the cache topic with id=" . $topic_id);
			return $topic;
		} catch (CacheException $e) { // If that fails, get the topic from the DB
			Log :: trace(__CLASS__, "Can't find topic in the cache, looking in the DB for topic with id=" . $topic_id);
			if (!Topic :: $statements_started)
				Topic :: prepareStatements();
			Log :: trace("DB", "Executing statement_getTopic");
			$result = Topic :: $statement_getTopic->execute($topic_id);

			if (!$result || PEAR::isError($result) || $result->numRows() != 1) {
				$topic = false;
			} else {
				$row = $result->fetchRow();
				$topic = new Topic();
				$topic->setId($row[$COLUMN["TOPIC_ID"]]);
				$topic->setUserId($row[$COLUMN["USER_ID"]]);
				$topic->setTitle($row[$COLUMN["TITLE"]]);
				$topic->setDescription($row[$COLUMN["DESCRIPTION"]]);
			}

			$result->free();
		}

		// We couldn't find that topic id in the database or in the cache
		if (!$topic) {
			throw new TopicException("The topic couldn't be found in the cache or the DB");
		} else {
			// Since we just fetched the topic from the DB, let's put him/her in the cache
			try {
				Cache :: set("Topic-" . $topic->getId(), $topic);
			} catch (CacheException $ex) {
				Log :: error(__CLASS__, $ex->getMessage());
			}
			return $topic;
		}
	}

	public static function createTopic($user_id, $title, $description) {
		Log :: trace(__CLASS__, "Create Topic");
		
		if (!Topic :: $statements_started)
			Topic :: prepareStatements();

		Log :: trace("DB", "Executing statement_createTopic");
		$result = Topic :: $statement_createTopic->execute(array (
			$user_id,
			$title,
			$description
		));
		if ($result != 1) throw new TopicException("Could not insert entry for topic in the DB");
		$topic = new Topic();
		$topic->setId(DB :: insertid());
		$topic->setUserId($user_id);
		$topic->setTitle($title);
		$topic->setDescription($description);
		
		try {
			Cache::delete("TopicListByUserId-".$user_id);
		} catch (CacheException $e) {}
		
		try {
			Cache :: set("Topic-" . $topic->getId(), $topic);
		} catch (CacheException $ex) {
			Log :: error(__CLASS__, $ex->getMessage());
		}

		try {
			Cache::delete("TopicList");
		} catch (CacheException $e) {}
		return $topic;
	}

	public static function getTopicList() {
		global $COLUMN;
		
		try {
			$list = Cache::get("TopicList");
			return $list;
		} catch (CacheException $e) {
			
		}

		if (!Topic :: $statements_started)
			Topic :: prepareStatements();
		$list = Array ();

		Log :: trace("DB", "Executing statement_getTopicList");
		$result = Topic :: $statement_getTopicList->execute(1);
		if (!$result || PEAR::isError($result) || $result->numRows() < 1) {
			return $list;
		}
		
		while ($row = $result->fetchRow()) {
			$topic_id = $row[$COLUMN["TOPIC_ID"]];
			$list[$topic_id] = TopicVote :: getTopicVotes($topic_id);
		}
		$result->free();
		
		try {
			Cache::set("TopicList", $list);
		} catch (CacheException $e) {}
		return $list;
	}
	
	public static function getListByUserId($user_id) {
		global $COLUMN;
		
		try {
			$list = Cache::get("TopicListByUserId-".$user_id);
			return $list;
		} catch (CacheException $e) {
			
		}

		if (!Topic :: $statements_started)
			Topic :: prepareStatements();
		$list = Array ();

		Log :: trace("DB", "Executing Topic::statement_getListByUserId");
		$result = Topic :: $statement_getListByUserId->execute($user_id);
		if (!$result || PEAR::isError($result) || $result->numRows() < 1) {
			return $list;
		}
		
		while ($row = $result->fetchRow()) {
			$topic_id = $row[$COLUMN["TOPIC_ID"]];
			$list[$topic_id] = TopicVote :: getTopicVotes($topic_id);
		}
		$result->free();
		
		try {
			Cache::set("TopicListByUserId-".$user_id, $list);
		} catch (CacheException $e) {}
		
		return $list;
	}

	public static function deleteTopic($topic_id) {
		if (!Topic :: $statements_started)
			Topic :: prepareStatements();

		Log :: trace(__CLASS__, "deleting topic with id=" . $topic_id);
		
		$old_topic = Topic::getTopic($topic_id);
		$user_id = $old_topic->getUserId();

		Log :: trace("DB", "Executing statement_deleteTopic");
		$result = Topic :: $statement_deleteTopic->execute($topic_id);

		if ($result != 1) {
			throw new TopicException("Could not delete topic entry for topic_id=" . $topic_id);
		}

		try {
			Cache :: delete("Topic-" . $topic_id);
		} catch (CacheException $ex) {
			Log :: error(__CLASS__, $ex->getMessage());
		}
		
		TopicVote::deleteTopicVotes($topic_id);
		
		try {
			Cache::delete("TopicList");
		} catch (CacheException $e) {}
		
		try {
			Cache::delete("TopicListByUserId-".$user_id);
		} catch (CacheException $e) {}
	}

	public function saveCache() {
		Log :: trace(__CLASS__, "updating cache entry of topic with id=" . $this->id);
		try {
			Cache :: setorreplace("Topic-" . $this->id, $this);
		} catch (CacheException $ex) {
			Log :: error(__CLASS__, $ex->getMessage());
		}
	}

	public function getId() {
		return $this->id;
	}

	public function setId($id) {
		$this->id = $id;
	}

	public function getUserId() {
		return $this->user_id;
	}

	public function setUserId($id) {
		$this->user_id = $id;
	}

	public function getTitle() {
		return stripslashes($this->title);
	}

	public function setTitle($id) {
		$this->title = $id;
	}

	public function getDescription() {
		return stripslashes($this->description);
	}

	public function setDescription($id) {
		$this->description = $id;
	}
	
	public function getStatus() {
		return $this->status;
	}

	public function setStatus($status, $persist=true) {
		$this->status = $status;
		
		if ($persist) {
			try {
			Cache::delete("TopicList");
		} catch (CacheException $e) {}
		
			if (!Topic :: $statements_started)
				Topic :: prepareStatements();

			Log :: trace("DB", "Executing Topic::statement_setStatus");
			Topic :: $statement_setStatus->execute(array($status, $this->getId()));
			$this->saveCache();
		}
	}

	public static function prepareStatements() {
		global $TABLE;
		global $COLUMN;
		global $DATABASE;
		global $COLUMN_TYPE;
		global $STATUS;

		Log :: trace(__CLASS__, "Preparing DB statements for this class");

		Topic :: $statement_getTopic = DB :: prepareRead("SELECT " .
		$COLUMN["TOPIC_ID"] . ", " . $COLUMN["TITLE"] . ", " . $COLUMN["DESCRIPTION"] . ", " . $COLUMN["USER_ID"] .
		" FROM " . $DATABASE["PREFIX"] . $TABLE["TOPIC"] .
		" WHERE " . $COLUMN["TOPIC_ID"] . " = ?", array (
			'integer'
		));

		Topic :: $statement_getTopicList = DB :: prepareRead("SELECT " .
		$COLUMN["TOPIC_ID"] . " FROM " . $DATABASE["PREFIX"] . $TABLE["TOPIC"] . " WHERE ? AND " . $COLUMN["STATUS"] . " = ".$STATUS["VOTING"], array (
			'integer'
		));
		
		Topic :: $statement_getListByUserId = DB :: prepareRead("SELECT " .
		$COLUMN["TOPIC_ID"] . " FROM " . $DATABASE["PREFIX"] . $TABLE["TOPIC"] . " WHERE " . $COLUMN["USER_ID"] . " = ? AND " . $COLUMN["STATUS"] . " = ".$STATUS["VOTING"], array (
			'text'
		));

		Topic :: $statement_createTopic = DB :: prepareWrite("INSERT INTO " .
		$DATABASE["PREFIX"] . $TABLE["TOPIC"] . " (" . $COLUMN["USER_ID"] . ", " . $COLUMN["TITLE"] . ", " . $COLUMN["DESCRIPTION"] . ") VALUES(?, ?, ?)", array (
			'text',
			'text',
			'text'
		));

		Topic :: $statement_deleteTopic = DB :: prepareWrite("DELETE FROM " .
		$DATABASE["PREFIX"] . $TABLE["TOPIC"] . " WHERE " . $COLUMN["TOPIC_ID"] . " = ?", array (
			'integer'
		));
		
		Topic :: $statement_setStatus = DB :: prepareWrite("UPDATE " .
		$DATABASE["PREFIX"] . $TABLE["TOPIC"] . " SET " . $COLUMN["STATUS"] . " = ? WHERE " . $COLUMN["TOPIC_ID"] . " = ?", array (
			'integer',
			'integer'
		));

		Topic :: $statements_started = true;
	}
}
?>