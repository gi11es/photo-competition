<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/

require_once (dirname(__FILE__) . '/../utilities/db.php');
require_once (dirname(__FILE__) . '/../utilities/cache.php');
require_once (dirname(__FILE__) . '/../utilities/log.php');
require_once (dirname(__FILE__) . '/../constants.php');
require_once (dirname(__FILE__) . '/../settings.php');

class TopicVoteException extends Exception {
}

class TopicVote {
	private $topic_id;
	private $user_id;
	private $value;

	private static $statements_started = false;
	private static $statement_getTopicVotes;
	private static $statement_getTopicVote;
	private static $statement_createTopicVote;
	private static $statement_setTopicVote;
	private static $statement_deleteTopicVotes;

	public static function setTopicVote($topic_id, $user_id, $value) {
		Log :: trace(__CLASS__, "Create Topic Vote entry");

		if (!TopicVote :: $statements_started)
			TopicVote :: prepareStatements();

		Log :: trace("DB", "Executing statement_createTopicVote");
		$result = TopicVote :: $statement_createTopicVote->execute(array (
			$topic_id,
			$user_id,
			$value
		));

		if (!$result || PEAR::isError($result)) {
			
			Log :: trace("DB", "Executing statement_setTopicVote");
			$result2 = TopicVote :: $statement_setTopicVote->execute(array (
				$value,
				$topic_id,
				$user_id
			));
		}

		$topicvote = new TopicVote();
		$topicvote->setTopicId($topic_id);
		$topicvote->setUserId($user_id);
		$topicvote->setValue($value);

		try {
			Cache :: set("TopicVote-" . $topic_id . "-" . $user_id, $topicvote);
		} catch (CacheException $ex) {
			Log :: error(__CLASS__, $ex->getMessage());
		}
		
		try {
			Cache::delete("TopicList");
		} catch (CacheException $e) {}

		return $topicvote;
	}
	
	public static function deleteTopicVotes($topic_id) {
		if (!TopicVote :: $statements_started)
			TopicVote :: prepareStatements();
	
		Log :: trace("DB", "Executing statement_deleteTopicVotes");
		TopicVote :: $statement_deleteTopicVotes->execute($topic_id);
	}
	
	public static function getTopicVotes($topic_id) {
		global $COLUMN;
		
		if (!TopicVote :: $statements_started)
			TopicVote :: prepareStatements();
	
		Log :: trace("DB", "Executing statement_getTopicVotes");
		$result = TopicVote :: $statement_getTopicVotes->execute($topic_id);

			if (!$result || $result->numRows() != 1) {
				$topicvotes = 0;
			} else {
				$row = $result->fetchRow();
				$topicvotes = $row[$COLUMN["VALUE"]];
			}

			$result->free();
			
		return $topicvotes;
	}
	
	public static function getTopicVote($topic_id, $user_id) {
		global $COLUMN;
		
		try {
			$topicvote = Cache :: get("TopicVote-" . $topic_id . "-" . $user_id);
			return $topicvote->getValue();
		} catch (CacheException $ex) {
			if (!TopicVote :: $statements_started)
			TopicVote :: prepareStatements();
			Log :: trace("DB", "Executing statement_getTopicVote");
			$result = TopicVote :: $statement_getTopicVote->execute(array($topic_id, $user_id));
			
			if (!$result || PEAR::isError($result)) {
				return false;
			} else {
				$row = $result->fetchRow();
				$result->free();
				return $row[$COLUMN["VALUE"]];
			}
		}
	}

	public function saveCache() {
		Log :: trace(__CLASS__, "updating cache entry of topic vote with topic id=" . $this->topic_id . " and user id=" . $this->user_id);
		try {
			Cache :: setorreplace("TopicVote-" . $this->topic_id . "-" . $this->user_id, $this);
		} catch (CacheException $ex) {
			Log :: error(__CLASS__, $ex->getMessage());
		}
	}

	public function getTopicId() {
		return $this->topic_id;
	}

	public function setTopicId($topic_id) {
		$this->topic_id = $topic_id;
	}

	public function getUserId() {
		return $this->user_id;
	}

	public function setUserId($id) {
		$this->user_id = $id;
	}

	public function getValue() {
		return $this->value;
	}

	public function setValue($value) {
		$this->value = $value;
	}

	public static function prepareStatements() {
		global $TABLE;
		global $COLUMN;
		global $DATABASE;
		global $COLUMN_TYPE;

		Log :: trace(__CLASS__, "Preparing DB statements for this class");

		TopicVote :: $statement_getTopicVotes = DB :: prepareRead("SELECT SUM(" .
		$COLUMN["VALUE"] . ") AS " .
		$COLUMN["VALUE"] .
		" FROM " . $DATABASE["PREFIX"] . $TABLE["TOPIC_VOTE"] .
		" WHERE " . $COLUMN["TOPIC_ID"] . " = ? GROUP BY " . $COLUMN["TOPIC_ID"], array (
			'integer'
		));
		
		TopicVote :: $statement_getTopicVote = DB :: prepareRead("SELECT ".
		$COLUMN["VALUE"] .
		" FROM " . $DATABASE["PREFIX"] . $TABLE["TOPIC_VOTE"] .
		" WHERE " . $COLUMN["TOPIC_ID"] . " = ? AND " . $COLUMN["USER_ID"] . " = ? ", array (
			'integer',
			'text'
		));

		TopicVote :: $statement_createTopicVote = DB :: prepareWrite("INSERT INTO " .
		$DATABASE["PREFIX"] . $TABLE["TOPIC_VOTE"] . " (" . $COLUMN["TOPIC_ID"] . ", " . $COLUMN["USER_ID"] . ", " . $COLUMN["VALUE"] . ") VALUES(?, ?, ?)", array (
			'integer',
			'text',
			'integer'
		));

		TopicVote :: $statement_setTopicVote = DB :: prepareWrite("UPDATE " .
		$DATABASE["PREFIX"] . $TABLE["TOPIC_VOTE"] . " SET " . $COLUMN["VALUE"] . " = ? WHERE " . $COLUMN["TOPIC_ID"] . " = ? AND " . $COLUMN["USER_ID"] . " = ?", array (
			'integer',
			'integer',
			'text'
		));
		
		TopicVote :: $statement_deleteTopicVotes = DB :: prepareWrite("DELETE FROM " .
		$DATABASE["PREFIX"] . $TABLE["TOPIC_VOTE"] . " WHERE " . $COLUMN["TOPIC_ID"] . " = ? ", array (
			'integer'
		));

		TopicVote :: $statements_started = true;
	}
}
?>