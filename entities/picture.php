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
require_once (dirname(__FILE__) . '/topic.php');
require_once (dirname(__FILE__) . '/topicvote.php');

class PictureException extends Exception {
}

class Picture {
	private $picture_id;
	private $user_id;
	private $topic_id;
	private $flagged;
	private $status;
	private $comments_allowed;

	private static $statements_started = false;
	private static $statement_getPicture;
	private static $statement_getPictureTopicList;
	private static $statement_getPictureUserList;
	private static $statement_getPictureList;
	private static $statement_createPicture;
	private static $statement_setFlagged;
	private static $statement_setStatus;
	private static $statement_setUserId;
	private static $statement_getUserEntry;
	private static $statement_setCommentsAllowed;

	public static function getPicture($newpicture_id, $newtopic_id) {
		global $TABLE;
		global $COLUMN;

		Log :: trace(__CLASS__, "retrieving picture with picture_id=" . $newpicture_id . " topic_id=" . $newtopic_id);

		// First try to retrieve the picture from the cache
		try {
			$picture = Cache :: get("Picture-" . $newpicture_id . "-" . $newtopic_id);
			Log :: trace(__CLASS__, "Found in the cache picture with picture_id=" . $newpicture_id . " topic_id=" . $newtopic_id);
			return $picture;
		} catch (CacheException $e) { // If that fails, get the picture from the DB
			Log :: trace(__CLASS__, "Can't find picture in the cache, looking in the DB for picture with picture_id=" . $newpicture_id . " topic_id=" . $newtopic_id);
					if (!Picture :: $statements_started)
			Picture :: prepareStatements();
			Log :: trace("DB", "Executing statement_getPicture");
			$result = Picture :: $statement_getPicture->execute(array (
				$newpicture_id,
				$newtopic_id
			));

			if (!$result || PEAR :: isError($result) || $result->numRows() != 1) {
				$picture = false;
			} else {
				$row = $result->fetchRow();
				$picture = new Picture();
				$picture->setPictureId($newpicture_id);
				$picture->setUserId($row[$COLUMN["USER_ID"]], false);
				$picture->setTopicId($newtopic_id);
				$picture->setFlagged($row[$COLUMN["FLAGGED"]], false);
				$picture->setStatus($row[$COLUMN["STATUS"]], false);
				$picture->setCommentsAllowed($row[$COLUMN["COMMENTS_ALLOWED"]], false);
				$result->free();
			}

		}

		// We couldn't find that picture in the database nor in the cache
		if (!$picture) {
			throw new PictureException("The picture couldn't be found in the cache or the DB with picture_id=" . $newpicture_id . " topic_id=" . $newtopic_id);
		} else {
			// Since we just fetched the picture from the DB, let's put it in the cache
			try {
				Cache :: set("Picture-" . $newpicture_id . "-" . $newtopic_id, $picture);
			} catch (CacheException $ex) {
				Log :: error(__CLASS__, $ex->getMessage());
			}
			return $picture;
		}
	}

	public static function createPicture($user_id, $picture_id, $topic_id) {
		global $STATUS;

		Log :: trace(__CLASS__, "Create Picture");

		if (!Picture :: $statements_started)
			Picture :: prepareStatements();

		Log :: trace("DB", "Executing statement_createPicture");
		$result = Picture :: $statement_createPicture->execute(array (
			$user_id,
			$picture_id,
			$topic_id
		));
		if (!$result || PEAR :: isError($result) || $result != 1)
			throw new PictureException("Could not insert entry for picture in the DB");
		$picture = new Picture();
		$picture->setPictureId($picture_id);
		$picture->setUserId($user_id);
		$picture->setTopicId($topic_id);
		$picture->setFlagged(0, false);
		$picture->setStatus($STATUS["ACTIVE"], false);
		$picture->setCommentsAllowed(1, false);
		try {
			Cache :: set("Picture-" . $picture_id . "-" . $topic_id, $picture);
		} catch (CacheException $ex) {
			Log :: error(__CLASS__, $ex->getMessage());
		}

		try {
			Cache :: delete("PictureTopicList-" . $topic_id);
		} catch (CacheException $e) {
		}

		try {
			Cache :: delete("PictureUserList-" . $user_id);
		} catch (CacheException $e) {
		}
		return $picture;
	}
	
	public static function setPictureTopicList($topic_id, $list) {
		try {
			Cache :: setorreplace("PictureTopicList-" . $topic_id, $list);
		} catch (CacheException $e) {
			
		}
	}

	public static function getPictureTopicList($topic_id) {
		global $COLUMN;
		global $STATUS;

		try {
			$list = Cache :: get("PictureTopicList-" . $topic_id);
			
			return $list;
		} catch (CacheException $e) {

		}

		if (!Picture :: $statements_started)
			Picture :: prepareStatements();
		$list = Array ();

		Log :: trace("DB", "Executing statement_getPictureTopicList");
		$result = Picture :: $statement_getPictureTopicList->execute($topic_id);
		if (!$result || PEAR :: isError($result) || $result->numRows() < 1) {
			return $list;
		}

		while ($row = $result->fetchRow()) {
			$user_id = $row[$COLUMN["USER_ID"]];
			$picture_id = $row[$COLUMN["PICTURE_ID"]];
			$list[$user_id] = $picture_id;
		}
		$result->free();

		try {
			Cache :: set("PictureTopicList-" . $topic_id, $list);
		} catch (CacheException $e) {
		}
		return $list;
	}

	public static function getPictureList() {
		global $COLUMN;

		if (!Picture :: $statements_started)
			Picture :: prepareStatements();
		$list = Array ();

		Log :: trace("DB", "Executing statement_getPictureList");
		$result = Picture :: $statement_getPictureList->execute(1);
		if (!$result || PEAR :: isError($result) || $result->numRows() < 1) {
			return $list;
		}

		while ($row = $result->fetchRow()) {
			$topic_id = $row[$COLUMN["TOPIC_ID"]];
			$user_id = $row[$COLUMN["USER_ID"]];
			$picture_id = $row[$COLUMN["PICTURE_ID"]];
			$list[$topic_id][$user_id] = $picture_id;
		}
		$result->free();
		
		return $list;
	}

	public static function getUserEntry($user_id, $topic_id) {
		global $COLUMN;

		try {
			$picture_id = Cache :: get("PictureUserEntry-" . $user_id . "-" . $topic_id);
			return Picture :: getPicture($picture_id, $topic_id);
		} catch (CacheException $e) {

		}

		if (!Picture :: $statements_started)
			Picture :: prepareStatements();

		Log :: trace("DB", "Executing statement_getUserEntry");
		$result = Picture :: $statement_getUserEntry->execute(array (
			$user_id,
			$topic_id
		));
		if (!$result || PEAR :: isError($result) || $result->numRows() < 1) {
			return false;
		}

		while ($row = $result->fetchRow()) {
			$picture_id = $row[$COLUMN["PICTURE_ID"]];
		}
		$result->free();

		try {
			Cache :: set("PictureUserEntry-" . $user_id, $topic_id);
		} catch (CacheException $e) {
		}
		return Picture :: getPicture($picture_id, $topic_id);
	}

	public static function getPictureUserList($user_id) {
		global $COLUMN;

		try {
			$list = Cache :: get("PictureUserList-" . $user_id);
			return $list;
		} catch (CacheException $e) {

		}

		if (!Picture :: $statements_started)
			Picture :: prepareStatements();
		$list = Array ();

		Log :: trace("DB", "Executing statement_getPictureUserList");
		$result = Picture :: $statement_getPictureUserList->execute($user_id);
		if (!$result || PEAR :: isError($result) || $result->numRows() < 1) {
			return $list;
		}

		while ($row = $result->fetchRow()) {
			$topic_id = $row[$COLUMN["TOPIC_ID"]];
			$picture_id = $row[$COLUMN["PICTURE_ID"]];
			$list[$topic_id] = $picture_id;
		}
		$result->free();

		try {
			Cache :: set("PictureUserList-" . $user_id, $list);
		} catch (CacheException $e) {
		}
		return $list;
	}

	public function saveCache() {
		Log :: trace(__CLASS__, "updating cache entry of picture with user_id=" . $this->user_id . " picture_id=" . $this->picture_id . " topic_id=" . $this->topic_id);
		try {
			Cache :: setorreplace("Picture-" . $this->picture_id . "-" . $this->topic_id, $this);
		} catch (CacheException $ex) {
			Log :: error(__CLASS__, $ex->getMessage());
		}
	}

	public function getPictureId() {
		return $this->picture_id;
	}

	public function setPictureId($id) {
		$this->picture_id = $id;
	}

	public function getUserId() {
		return $this->user_id;
	}

	public function setUserId($id, $persist = true) {

		$this->user_id = $id;

		if ($persist) {

			if (!Picture :: $statements_started)
				Picture :: prepareStatements();

			Log :: trace("DB", "Executing statement_setUserId");
			Picture :: $statement_setUserId->execute(array (
				$id,
			$this->getPictureId(), $this->getTopicId()));
			$this->saveCache();
		}
	}

	public function getTopicId() {
		return $this->topic_id;
	}

	public function setTopicId($id) {
		$this->topic_id = $id;
	}

	public function getFlagged() {
		return $this->flagged;
	}

	public function setFlagged($flagged, $persist = true) {
		global $STATUS;

		$this->flagged = $flagged;

		if ($persist) {
			if ($flagged > 4 && $this->status == $STATUS["ACTIVE"])
				$this->setStatus($STATUS["FLAGGED"]);

			if (!Picture :: $statements_started)
				Picture :: prepareStatements();

			Log :: trace("DB", "Executing statement_setFlagged");
			Picture :: $statement_setFlagged->execute(array (
				$flagged,
			$this->getPictureId(), $this->getTopicId()));
			$this->saveCache();
		}
	}

	public function getStatus() {
		return $this->status;
	}

	public function setStatus($status, $persist = true) {
		$this->status = $status;

		if ($persist) {
			try {
				Cache :: delete("PictureTopicList-" . $this->getTopicId());
			} catch (CacheException $e) {
			}

			try {
				Cache :: delete("PictureUserList-" . $this->getUserId());
			} catch (CacheException $e) {
			}

			try {
				Cache :: delete("PictureUserEntry-" . $this->getUserId() . "-" . $this->getTopicId());
			} catch (CacheException $e) {
			}

			if (!Picture :: $statements_started)
				Picture :: prepareStatements();

			Log :: trace("DB", "Executing Picture::statement_setStatus");
			Picture :: $statement_setStatus->execute(array (
				$status,
			$this->getPictureId(), $this->getTopicId()));
			$this->saveCache();
		}
	}

	public function getCommentsAllowed() {
		return $this->comments_allowed;
	}

	public function setCommentsAllowed($allow, $persist = true) {
		$this->comments_allowed = $allow;

		if ($persist) {
			if (!Picture :: $statements_started)
				Picture :: prepareStatements();

			Log :: trace("DB", "Executing statement_setCommentsAllowed");
			Picture :: $statement_setCommentsAllowed->execute(array (
				$allow,
			$this->getPictureId(), $this->getTopicId()));
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

		Picture :: $statement_getPicture = DB :: prepareRead("SELECT " .
		$COLUMN["FLAGGED"] . ", " . $COLUMN["STATUS"] . ", " . $COLUMN["USER_ID"] . ", " . $COLUMN["COMMENTS_ALLOWED"] .
		" FROM " . $DATABASE["PREFIX"] . $TABLE["PICTURE"] .
		" WHERE " . $COLUMN["PICTURE_ID"] . " = ? AND " . $COLUMN["TOPIC_ID"] . " = ?", array (
			'text',
			'integer'
		));

		Picture :: $statement_getPictureTopicList = DB :: prepareRead("SELECT " .
		$COLUMN["USER_ID"] . ", " .
		$COLUMN["PICTURE_ID"] . " FROM " . $DATABASE["PREFIX"] . $TABLE["PICTURE"] . " WHERE " . $COLUMN["TOPIC_ID"] . " = ? AND " . $COLUMN["STATUS"] . " = " . $STATUS["ACTIVE"], array (
			'integer'
		));
		
		Picture :: $statement_getPictureList = DB :: prepareRead("SELECT " .
		$COLUMN["USER_ID"] . ", " .
		$COLUMN["TOPIC_ID"] . ", " .
		$COLUMN["PICTURE_ID"] . " FROM " . $DATABASE["PREFIX"] . $TABLE["PICTURE"] . " WHERE ? AND " . $COLUMN["STATUS"] . " = " . $STATUS["ACTIVE"], array (
			'integer'
		));

		Picture :: $statement_getPictureUserList = DB :: prepareRead("SELECT " .
		$COLUMN["PICTURE_ID"] . ", " .
		$COLUMN["TOPIC_ID"] . " FROM " . $DATABASE["PREFIX"] . $TABLE["PICTURE"] . " WHERE " . $COLUMN["USER_ID"] . " = ? AND " . $COLUMN["STATUS"] . " = " . $STATUS["ACTIVE"], array (
			'text'
		));

		Picture :: $statement_createPicture = DB :: prepareWrite("INSERT INTO " .
		$DATABASE["PREFIX"] . $TABLE["PICTURE"] . " (" . $COLUMN["USER_ID"] . ", " . $COLUMN["PICTURE_ID"] . ", " . $COLUMN["TOPIC_ID"] . ") VALUES(?, ?, ?)", array (
			'text',
			'text',
			'integer'
		));

		Picture :: $statement_getUserEntry = DB :: prepareRead("SELECT " .
		$COLUMN["PICTURE_ID"] . " FROM " . $DATABASE["PREFIX"] . $TABLE["PICTURE"] . " WHERE " . $COLUMN["USER_ID"] . " = ?  AND " . $COLUMN["TOPIC_ID"] . " = ? AND " . $COLUMN["STATUS"] . " = " . $STATUS["ACTIVE"], array (
			'text',
			'integer'
		));

		Picture :: $statement_setFlagged = DB :: prepareWrite("UPDATE " .
		$DATABASE["PREFIX"] . $TABLE["PICTURE"] . " SET " . $COLUMN["FLAGGED"] . " = ? WHERE " . $COLUMN["PICTURE_ID"] . " = ? AND " . $COLUMN["TOPIC_ID"] . " = ?", array (
			'integer',
			'text',
			'integer'
		));

		Picture :: $statement_setStatus = DB :: prepareWrite("UPDATE " .
		$DATABASE["PREFIX"] . $TABLE["PICTURE"] . " SET " . $COLUMN["STATUS"] . " = ? WHERE " . $COLUMN["PICTURE_ID"] . " = ? AND " . $COLUMN["TOPIC_ID"] . " = ?", array (
			'integer',
			'text',
			'integer'
		));

		Picture :: $statement_setUserId = DB :: prepareWrite("UPDATE " .
		$DATABASE["PREFIX"] . $TABLE["PICTURE"] . " SET " . $COLUMN["USER_ID"] . " = ? WHERE " . $COLUMN["PICTURE_ID"] . " = ? AND " . $COLUMN["TOPIC_ID"] . " = ?", array (
			'text',
			'text',
			'integer'
		));

		Picture :: $statement_setCommentsAllowed = DB :: prepareWrite("UPDATE " .
		$DATABASE["PREFIX"] . $TABLE["PICTURE"] . " SET " . $COLUMN["COMMENTS_ALLOWED"] . " = ? WHERE " . $COLUMN["PICTURE_ID"] . " = ? AND " . $COLUMN["TOPIC_ID"] . " = ?", array (
			'integer',
			'text',
			'integer'
		));

		Picture :: $statements_started = true;
	}
}
?>