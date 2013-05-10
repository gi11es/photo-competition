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
require_once (dirname(__FILE__) . '/picture.php');
require_once (dirname(__FILE__) . '/picturevote.php');

require_once("MDB2/Date.php");

class CompetitionException extends Exception {
}

class Competition {
	private $topic_id;
	private $start_time;
	private $status;

	private static $statements_started = false;
	private static $statement_getCompetition;
	private static $statement_getOpenCompetitionList;
	private static $statement_getVotingCompetitionList;
	private static $statement_getClosedCompetitionList;
	private static $statement_getRecentCompetitionList;
	private static $statement_createCompetition;
	private static $statement_setStatus;

	public static function getCompetition($topic_id) {
		global $TABLE;
		global $COLUMN;

		Log :: trace(__CLASS__, "retrieving competition with id=" . $topic_id);

		// First try to retrieve the competition from the cache
		try {
			$competition = Cache :: get("Competition-" . $topic_id);
			Log :: trace(__CLASS__, "Found in the cache competition with id=" . $topic_id);
			return $competition;
		} catch (CacheException $e) { // If that fails, get the competition from the DB
			Log :: trace(__CLASS__, "Can't find competition in the cache, looking in the DB for competition with id=" . $topic_id);
					if (!Competition :: $statements_started)
			Competition :: prepareStatements();
			Log :: trace("DB", "Executing statement_getCompetition");
			$result = Competition :: $statement_getCompetition->execute($topic_id);

			if (!$result || $result->numRows() != 1) {
				$competition = false;
			} else {
				$row = $result->fetchRow();
				$competition = new Competition();
				$competition->setTopicId($row[$COLUMN["TOPIC_ID"]]);
				$competition->setStartTime($row[$COLUMN["START_TIME"]]);
				$competition->setStatus($row[$COLUMN["STATUS"]], false);
			}

			$result->free();
		}

		// We couldn't find that competition id in the database or in the cache
		if (!$competition) {
			throw new CompetitionException("The competition couldn't be found in the cache or the DB");
		} else {
			// Since we just fetched the competition from the DB, let's put him/her in the cache
			try {
				Cache :: set("Competition-" . $competition->getTopicId(), $competition);
			} catch (CacheException $ex) {
				Log :: error(__CLASS__, $ex->getMessage());
			}
			return $competition;
		}
	}

	public static function createCompetition($topic_id) {
		global $STATUS;

		Log :: trace(__CLASS__, "Create Competition");

		if (!Competition :: $statements_started)
			Competition :: prepareStatements();

		Log :: trace("DB", "Executing statement_createCompetition");
		$result = Competition :: $statement_createCompetition->execute($topic_id);
		if (PEAR :: isError($result) || $result != 1)
			throw new CompetitionException("Could not insert entry for competition in the DB");
		$competition = new Competition();
		$competition->setTopicId($topic_id);
		$competition->setStartTime(time());
		$competition->setStatus($STATUS["OPEN"]);

		try {
			Cache :: set("Competition-" . $topic_id, $competition);
		} catch (CacheException $ex) {
			Log :: error(__CLASS__, $ex->getMessage());
		}

		try {
			Cache :: delete("OpenCompetitionList");
		} catch (CacheException $e) {
		}

		$topic = Topic :: getTopic($topic_id);
		$topic->setStatus($STATUS["CLOSED"]);

		return $competition;
	}

	public static function getOpenCompetitionList() {
		global $COLUMN;

		try {
			$list = Cache :: get("OpenCompetitionList");
			return $list;
		} catch (CacheException $e) {

		}

		if (!Competition :: $statements_started)
			Competition :: prepareStatements();
		$list = Array ();

		Log :: trace("DB", "Executing statement_getOpenCompetitionList");
		$result = Competition :: $statement_getOpenCompetitionList->execute(1);
		if (!$result || PEAR :: isError($result) || $result->numRows() < 1) {
			return $list;
		}

		while ($row = $result->fetchRow()) {
			$topic_id = $row[$COLUMN["TOPIC_ID"]];
			$competition = Competition :: getCompetition($topic_id);
			$list[$topic_id] = $competition->getStartTime();
		}
		$result->free();

		try {
			Cache :: set("OpenCompetitionList", $list);
		} catch (CacheException $e) {
		}
		return $list;
	}

	public static function getVotingCompetitionList() {
		global $COLUMN;

		try {
			$list = Cache :: get("VotingCompetitionList");
			return $list;
		} catch (CacheException $e) {

		}

		if (!Competition :: $statements_started)
			Competition :: prepareStatements();
		$list = Array ();

		Log :: trace("DB", "Executing statement_getVotingCompetitionList");
		$result = Competition :: $statement_getVotingCompetitionList->execute(1);
		if (!$result || PEAR :: isError($result) || $result->numRows() < 1) {
			return $list;
		}

		while ($row = $result->fetchRow()) {
			$topic_id = $row[$COLUMN["TOPIC_ID"]];
			$competition = Competition :: getCompetition($topic_id);
			$list[$topic_id] = $competition->getStartTime();
		}
		$result->free();

		try {
			Cache :: set("VotingCompetitionList", $list);
		} catch (CacheException $e) {
		}
		return $list;
	}

	public static function getClosedCompetitionList() {
		global $COLUMN;

		try {
			$list = Cache :: get("ClosedCompetitionList");
			return $list;
		} catch (CacheException $e) {

		}

		if (!Competition :: $statements_started)
			Competition :: prepareStatements();
		$list = Array ();

		Log :: trace("DB", "Executing statement_getClosedCompetitionList");
		$result = Competition :: $statement_getClosedCompetitionList->execute(1);
		if (!$result || PEAR :: isError($result) || $result->numRows() < 1) {
			return $list;
		}

		while ($row = $result->fetchRow()) {
			$topic_id = $row[$COLUMN["TOPIC_ID"]];
			$competition = Competition :: getCompetition($topic_id);
			$list[$topic_id] = $competition->getStartTime();
		}
		$result->free();

		try {
			Cache :: set("ClosedCompetitionList", $list);
		} catch (CacheException $e) {
		}
		return $list;
	}
        
        public static function getRecentCompetitionList($start_timestamp) {
		global $COLUMN;
		
		try {
			$list = Cache :: get("RecentCompetitionList-".$start_timestamp);
			return $list;
		} catch (CacheException $e) {}
		
		if (!Competition :: $statements_started)
			Competition :: prepareStatements();
			
		$list = Array ();
		
		Log :: trace("DB", "Executing statement_getRecentCompetitionList");
		$result = Competition :: $statement_getRecentCompetitionList->execute(MDB2_Date::unix2Mdbstamp($start_timestamp));
		if (!$result || PEAR :: isError($result) || $result->numRows() < 1) {
			return $list;
		}

		while ($row = $result->fetchRow()) {
			$topic_id = $row[$COLUMN["TOPIC_ID"]];
			$competition = Competition :: getCompetition($topic_id);
			$list[$topic_id] = $competition->getStartTime();
		}
		$result->free();

		try {
			Cache :: set("RecentCompetitionList-".$start_timestamp, $list);
		} catch (CacheException $e) {
		}
		return $list;
        }

	public function saveCache() {
		Log :: trace(__CLASS__, "updating cache entry of competition with id=" . $this->topic_id);
		try {
			Cache :: setorreplace("Competition-" . $this->topic_id, $this);
		} catch (CacheException $ex) {
			Log :: error(__CLASS__, $ex->getMessage());
		}
	}

	public function getTopicId() {
		return $this->topic_id;
	}

	public function setTopicId($id) {
		$this->topic_id = $id;
	}

	public function getStartTime() {
		return $this->start_time;
	}

	public function setStartTime($id) {
		$this->start_time = $id;
	}

	public function getStatus() {
		return $this->status;
	}

	public function getRanks($add_ending = true) {
		$pictures = Picture :: getPictureTopicList($this->getTopicId());
		$votes = array ();
		$ranked = array();
		foreach ($pictures as $user_id => $picture_id)
			$votes[$picture_id] = PictureVote :: getPictureVotes($picture_id, $this->getTopicId());
		arsort($votes);
		$position = 1;
		$actual_position = 1;
		$old_value = -1000;
		foreach ($votes as $key => $value) {
			if ($value == $old_value) {
				$ranked[$key] = $position;
			} else {
				$position = $actual_position;
				$ranked[$key] = $position;
			}

			if ($add_ending) {
				if (substr($ranked[$key], -1) == 1 && $ranked[$key] != 11)
					$ranked[$key] .= "st";
				elseif (substr($ranked[$key], -1) == 2 && $ranked[$key] != 12) $ranked[$key] .= "nd";
				elseif (substr($ranked[$key], -1) == 3 && $ranked[$key] != 13) $ranked[$key] .= "rd";
				else
					$ranked[$key] .= "th";
			}

			$old_value = $value;
			$actual_position++;
		}
		
		return $ranked;
	}

	public function setStatus($newstatus, $persist = true) {
		Log :: trace(__CLASS__, "Setting status for competition with topic id=" . $this->topic_id . " to " . $newstatus);
		$this->status = $newstatus;

		if ($persist) {
			try {
				Cache :: delete("OpenCompetitionList");
			} catch (CacheException $e) {
			}

			try {
				Cache :: delete("VotingCompetitionList");
			} catch (CacheException $e) {
			}

			try {
				Cache :: delete("ClosedCompetitionList");
			} catch (CacheException $e) {
			}

			if (!Competition :: $statements_started)
				Competition :: prepareStatements();

			Log :: trace("DB", "Executing Competition::statement_setStatus");
			Competition :: $statement_setStatus->execute(array (
				$newstatus,
			$this->getTopicId()));
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

		Competition :: $statement_getCompetition = DB :: prepareRead("SELECT " .
		$COLUMN["TOPIC_ID"] . ", UNIX_TIMESTAMP(" . $COLUMN["START_TIME"] . ") AS " . $COLUMN["START_TIME"] . ", " . $COLUMN["STATUS"] .
		" FROM " . $DATABASE["PREFIX"] . $TABLE["COMPETITION"] .
		" WHERE " . $COLUMN["TOPIC_ID"] . " = ?", array (
			'integer'
		));

		Competition :: $statement_getOpenCompetitionList = DB :: prepareRead("SELECT " .
		$COLUMN["TOPIC_ID"] . " FROM " . $DATABASE["PREFIX"] . $TABLE["COMPETITION"] . " WHERE ? AND " . $COLUMN["STATUS"] . " = " . $STATUS["OPEN"], array (
			'integer'
		));

		Competition :: $statement_getVotingCompetitionList = DB :: prepareRead("SELECT " .
		$COLUMN["TOPIC_ID"] . " FROM " . $DATABASE["PREFIX"] . $TABLE["COMPETITION"] . " WHERE ? AND " . $COLUMN["STATUS"] . " = " . $STATUS["VOTING"], array (
			'integer'
		));

		Competition :: $statement_getClosedCompetitionList = DB :: prepareRead("SELECT " .
		$COLUMN["TOPIC_ID"] . " FROM " . $DATABASE["PREFIX"] . $TABLE["COMPETITION"] . " WHERE ? AND " . $COLUMN["STATUS"] . " = " . $STATUS["CLOSED"], array (
			'integer'
		));
	
		Competition :: $statement_getRecentCompetitionList = DB :: prepareRead("SELECT " .
		$COLUMN["TOPIC_ID"] . " FROM " . $DATABASE["PREFIX"] . $TABLE["COMPETITION"] . " WHERE " . $COLUMN["START_TIME"] . " > ?", array (
			'timestamp'
		));

		Competition :: $statement_createCompetition = DB :: prepareWrite("INSERT INTO " .
		$DATABASE["PREFIX"] . $TABLE["COMPETITION"] . " (" . $COLUMN["TOPIC_ID"] . ") VALUES(?)", array (
			'integer'
		));

		Competition :: $statement_setStatus = DB :: prepareWrite("UPDATE " .
		$DATABASE["PREFIX"] . $TABLE["COMPETITION"] . " SET " . $COLUMN["STATUS"] . " = ? WHERE " . $COLUMN["TOPIC_ID"] . " = ?", array (
			'integer',
			'integer'
		));

		Competition :: $statements_started = true;
	}
}
?>