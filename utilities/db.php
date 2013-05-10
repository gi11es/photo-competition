<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/

require_once(dirname(__FILE__)."/../constants.php");
require_once(dirname(__FILE__)."/../settings.php");
require_once(dirname(__FILE__)."/log.php");

require_once "MDB2.php";

class DBException extends Exception {}

class DB {
	private static $started = false;
	private static $mysql = null;

	// Since the class is static we use that method to replace a constructor
	private static function initCheck(){
		global $DATABASE;

		if (!DB::$started) {
			Log::trace(__CLASS__, "*** starting ***");
		//	DB::$mysql = MDB2::connect("mysql://".$DATABASE["USER"].":".$DATABASE["PASSWORD"]."@".$DATABASE["HOST"]."/".$DATABASE["NAME"]."");

			 //MDB2 Data Source Name
	        $dsn = array(
	                'phptype'  => 'mysqli',
	                'hostspec' => $DATABASE["HOST"],
	                'username' => $DATABASE["USER"],
	                'password' => $DATABASE["PASSWORD"],
	                'database' => $DATABASE["NAME"],
	                'charset' => 'UTF8'
	        );
	        //MDB2 options
	        $options = array(
	                'debug'       => 2,
	                'portability' => MDB2_PORTABILITY_ALL
	        );

			DB::$mysql = MDB2::singleton($dsn, $options);
			DB::$mysql->connect();
			if (PEAR::isError(DB::$mysql)) {
				Log::critical(__CLASS__, "Can't connect to the database. ".DB::$mysql->getMessage());
				throw new DBException("Can't connect to the database. ".DB::$mysql->getMessage());
			}
				
			DB::$mysql->setFetchMode(MDB2_FETCHMODE_ASSOC);
			//DB::$mysql->query("set NAMES 'UTF8'");

			// Register a cleanup method that will be called automatically upon class destruction
			register_shutdown_function(array("DB", "shutdown"));
			DB::$started = true;
		}
	}

	// Cleanup method, equivalent of a destructor
	public static function shutdown() {
		if (DB::$started) {
			Log::trace(__CLASS__, "*** stopping ***");
			DB::$mysql->disconnect();
			DB::$started = false;
		}
	}	
	
	// Prepare a write statement
	public static function prepareWrite($query, $types) {
		DB::initCheck();
	
		return DB::$mysql->prepare($query, $types, MDB2_PREPARE_MANIP);
	}
	
	public static function prepareSetter($table, $key, $field, $field_type="integer", $key_type="integer") {
		global $DATABASE;

		return DB::prepareWrite( 
				"UPDATE ".$DATABASE["PREFIX"].$table." SET ".$field." = ? WHERE ".$key." = ?"
						, array($field_type, $key_type));
	}

	// Prepare a read statement	
	public static function prepareRead($query, $types) {
		DB::initCheck();
	
		return DB::$mysql->prepare($query, $types, MDB2_PREPARE_RESULT);
	}

	// Oldschool unprepared query	
	public static function query($query) {
		DB::initCheck();
	
		return DB::$mysql->query($query);
	}
	
	// Drop a table form DB, USE WITH CAUTION
	public static function dropTable($tablename) {
		global $DATABASE;
		global $TABLE;
		DB::initCheck();
		
		DB::$mysql->query("DROP TABLE IF EXISTS ".$DATABASE["PREFIX"].$TABLE[$tablename]);
	}
	
	// Change the primary key of a table, USE WITH CAUTION
	public static function alterTablePrimaryKey($tablename, $keyarray) {
		global $DATABASE;
		global $TABLE;
		global $COLUMN;
		
		DB::initCheck();
		
		// generate a string with the list of columns in a "COLUMN1, COLUMN2, COLUMN3" syntax
		$creationstring = "";
		foreach ($keyarray as $column) {
			$creationstring .= $COLUMN[$column].", ";
		}
		// Strip the last ", "
		$creationstring = mb_substr($creationstring, 0, -2);
		
		DB::$mysql->query("ALTER TABLE ".$DATABASE["PREFIX"].$TABLE[$tablename]." DROP PRIMARY KEY");
		DB::$mysql->query("ALTER TABLE ".$DATABASE["PREFIX"].$TABLE[$tablename]." ADD PRIMARY KEY ( ".$creationstring." )");
	}
	
	// Returns the latest auto-increment id that"s been inserted
	public static function insertid() {
		return DB::$mysql->lastInsertID();
	}
}

?>