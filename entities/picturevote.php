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

class PictureVoteException extends Exception {
}

class PictureVote {
	private $picture_id;
	private $topic_id;
	private $voter_id;
	private $value;

	private static $statements_started = false;
	private static $statement_getPictureVotes;
	private static $statement_getPictureVoteList;
	private static $statement_getPictureVoteUserList;
	private static $statement_getPictureVote;
	private static $statement_createPictureVote;
	private static $statement_setPictureVote;
	private static $statement_delete;

	public static function setPictureVote($picture_id, $topic_id, $voter_id, $value) {
		Log :: trace(__CLASS__, "Create Picture Vote entry");
		
		try {
			Cache::delete("PictureVotes-" . $picture_id . "-" . $topic_id);
		} catch (CacheException $e) {}
		try {
			Cache::delete("PictureVoteList-" . $picture_id . "-" . $topic_id);
		} catch (CacheException $e) {}

		if (!PictureVote :: $statements_started)
			PictureVote :: prepareStatements();

		Log :: trace("DB", "Executing statement_createPictureVote");
		$result = PictureVote :: $statement_createPictureVote->execute(array (
			$picture_id,
			$topic_id,
			$voter_id,
			$value
		));

		if (!$result || PEAR :: isError($result)) {

			Log :: trace("DB", "Executing statement_setPictureVote");
			$result2 = PictureVote :: $statement_setPictureVote->execute(array (
				$value,
				$picture_id,
				$topic_id,
				$voter_id
			));
		}

		$picturevote = new PictureVote();
		$picturevote->setTopicId($topic_id);
		$picturevote->setValue($value);

		try {
			Cache :: set("PictureVote-" . $picture_id . "-" . $topic_id . "-" . $voter_id, $picturevote);
		} catch (CacheException $ex) {
			Log :: error(__CLASS__, $ex->getMessage());
		}

		return $picturevote;
	}

	public static function getPictureVotes($picture_id, $topic_id) {
		global $COLUMN;
		
		try {
			$picturevotes = Cache :: get("PictureVotes-" . $picture_id . "-" . $topic_id);
			return $picturevotes;
		} catch (CacheException $ex) {
			if (!PictureVote :: $statements_started)
				PictureVote :: prepareStatements();
	
			Log :: trace("DB", "Executing statement_getPictureVotes $picture_id $topic_id");
			$result = PictureVote :: $statement_getPictureVotes->execute(array (
				$picture_id,
				$topic_id
			));
	
			if (!$result || $result->numRows() != 1) {
				$picturevotes = 0;
			} else {
				$row = $result->fetchRow();
				$picturevotes = $row[$COLUMN["VALUE"]];
			}
	
			$result->free();
			
			try {
				Cache :: set("PictureVotes-" . $picture_id . "-" . $topic_id, $picturevotes);
			} catch (CacheException $ex) {
				Log :: error(__CLASS__, $ex->getMessage());
			}
	
			return $picturevotes;
		}
	}

	public static function getPictureVoteList($picture_id, $topic_id) {
		global $COLUMN;

		try {
			$picturevotes = Cache :: get("PictureVoteList-" . $picture_id . "-" . $topic_id);
			return $picturevotes;
		} catch (CacheException $ex) {
			if (!PictureVote :: $statements_started)
				PictureVote :: prepareStatements();
	
			Log :: trace("DB", "Executing statement_getPictureVoteList");
			$result = PictureVote :: $statement_getPictureVoteList->execute(array (
				$picture_id,
				$topic_id
			));
	
			$picturevotes = array ();
	
			if (!$result || $result->numRows() < 1) {
	
			} else
				while ($row = $result->fetchRow()) {
					$picturevotes[$row[$COLUMN["VOTER_ID"]]] = $row[$COLUMN["VALUE"]];
				}
	
			$result->free();
			
			try {
				Cache :: set("PictureVoteList-" . $picture_id . "-" . $topic_id, $picturevotes);
			} catch (CacheException $ex) {
				Log :: error(__CLASS__, $ex->getMessage());
			}
	
			return $picturevotes;
		}
	}
	
	public static function getPictureVoteUserList($voter_id) {
		global $COLUMN;

		if (!PictureVote :: $statements_started)
			PictureVote :: prepareStatements();

		Log :: trace("DB", "Executing statement_getPictureVoteUserList");
		$result = PictureVote :: $statement_getPictureVoteUserList->execute(
			$voter_id);

		$picturevotes = array ();

		if (!$result || PEAR :: isError($result) || $result->numRows() < 1) {

		} else {
			while ($row = $result->fetchRow()) {
				$picturevotes[$row[$COLUMN["PICTURE_ID"]]] [$row[$COLUMN["TOPIC_ID"]]] = $row[$COLUMN["VALUE"]];
			}
		}
		
		if (!PEAR :: isError($result) && $result)
			$result->free();

		return $picturevotes;
	}

	public static function getPictureVote($picture_id, $topic_id, $voter_id) {
		global $COLUMN;

		try {
			$picturevote = Cache :: get("PictureVote-" . $picture_id . "-" . $topic_id . "-" . $voter_id);
			return $picturevote->getValue();
		} catch (CacheException $ex) {
			if (!PictureVote :: $statements_started)
			PictureVote :: prepareStatements();
			
			Log :: trace("DB", "Executing statement_getPictureVote");
			$result = PictureVote :: $statement_getPictureVote->execute(array (
				$picture_id,
				$topic_id,
				$voter_id
			));

			if (!$result || PEAR :: isError($result)) {
				return false;
			} else {
				$row = $result->fetchRow();
				$result->free();
				return $row[$COLUMN["VALUE"]];
			}
		}
	}

	public function saveCache() {
		Log :: trace(__CLASS__, "updating cache entry of picture vote with picture_id=" . $this->picture_id . " topic_id=" . $this->topic_id . " voter_id=" . $this->voter_id);
		try {
			Cache :: setorreplace("PictureVote-" . $this->picture_id . "-" . $this->topic_id . "-" . $this->voter_id, $this);
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

	public function getVoterId() {
		return $this->voter_id;
	}

	public function setVoterId($voter_id) {
		$this->voter_id = $voter_id;
	}

	public function getPictureId() {
		return $this->picture_id;
	}

	public function setPictureId($picture_id) {
		$this->picture_id = $picture_id;
	}

	public function getValue() {
		return $this->value;
	}

	public function setValue($value) {
		$this->value = $value;
	}
	
	public static function deletePictureVote($picture_id, $topic_id, $voter_id) {
		try {
			Cache::delete("PictureVotes-" . $picture_id . "-" . $topic_id);
		} catch (CacheException $e) {}
		try {
			Cache::delete("PictureVoteList-" . $picture_id . "-" . $topic_id);
		} catch (CacheException $e) {}
		try {
			Cache :: delete("PictureVote-" . $picture_id . "-" . $topic_id . "-" . $voter_id);
		} catch (CacheException $e) {}
		
		if (!PictureVote :: $statements_started)
			PictureVote :: prepareStatements();
		
		Log :: trace("DB", "Executing PictureVote::statement_delete");
		PictureVote :: $statement_delete->execute(array (
				$picture_id,
				$topic_id,
				$voter_id
			));
	}

	public static function prepareStatements() {
		global $TABLE;
		global $COLUMN;
		global $DATABASE;
		global $COLUMN_TYPE;

		Log :: trace(__CLASS__, "Preparing DB statements for this class");

		PictureVote :: $statement_getPictureVotes = DB :: prepareRead("SELECT SUM(" .
		$COLUMN["VALUE"] . ") AS " .
		$COLUMN["VALUE"] .
		" FROM " . $DATABASE["PREFIX"] . $TABLE["PICTURE_VOTE"] .
		" WHERE " . $COLUMN["PICTURE_ID"] . " = ? AND " . $COLUMN["TOPIC_ID"] . " = ? ", array (
			'text',
			'integer'
		));

		PictureVote :: $statement_getPictureVoteList = DB :: prepareRead("SELECT " .
		$COLUMN["VALUE"] . ", ".$COLUMN["VOTER_ID"].
		" FROM " . $DATABASE["PREFIX"] . $TABLE["PICTURE_VOTE"] .
		" WHERE " . $COLUMN["PICTURE_ID"] . " = ? AND " . $COLUMN["TOPIC_ID"] . " = ? ", array (
			'text',
			'integer'
		));
		
		PictureVote :: $statement_getPictureVoteUserList = DB :: prepareRead("SELECT " .
		$COLUMN["VALUE"] . ", ".$COLUMN["PICTURE_ID"]. ", ".$COLUMN["TOPIC_ID"].
		" FROM " . $DATABASE["PREFIX"] . $TABLE["PICTURE_VOTE"] .
		" WHERE " . $COLUMN["VOTER_ID"] . " = ?", array (
			'text'
		));

		PictureVote :: $statement_getPictureVote = DB :: prepareRead("SELECT " .
		$COLUMN["VALUE"] .
		" FROM " . $DATABASE["PREFIX"] . $TABLE["PICTURE_VOTE"] .
		" WHERE " . $COLUMN["PICTURE_ID"] . " = ? AND " . $COLUMN["TOPIC_ID"] . " = ? AND " . $COLUMN["VOTER_ID"] . " = ?", array (
			'text',
			'integer',
			'text'
		));

		PictureVote :: $statement_createPictureVote = DB :: prepareWrite("INSERT INTO " .
		$DATABASE["PREFIX"] . $TABLE["PICTURE_VOTE"] . " (" . $COLUMN["PICTURE_ID"] . ", " . $COLUMN["TOPIC_ID"] . ", " . $COLUMN["VOTER_ID"] . ", " . $COLUMN["VALUE"] . ") VALUES(?, ?, ?, ?)", array (
			'text',
			'integer',
			'text',
			'integer'
		));

		PictureVote :: $statement_setPictureVote = DB :: prepareWrite("UPDATE " .
		$DATABASE["PREFIX"] . $TABLE["PICTURE_VOTE"] . " SET " . $COLUMN["VALUE"] . " = ? WHERE " . $COLUMN["PICTURE_ID"] . " = ? AND " . $COLUMN["TOPIC_ID"] . " = ? AND " . $COLUMN["VOTER_ID"] . " = ?", array (
			'integer',
			'text',
			'integer',
			'text'
		));
		
		PictureVote :: $statement_delete = DB :: prepareWrite("DELETE FROM ".
		$DATABASE["PREFIX"] . $TABLE["PICTURE_VOTE"]. " WHERE " . $COLUMN["PICTURE_ID"] . " = ? AND " . $COLUMN["TOPIC_ID"] . " = ? AND " . $COLUMN["VOTER_ID"] . " = ?", array (
			'text',
			'integer',
			'text'
		));

		PictureVote :: $statements_started = true;
	}
}
?>