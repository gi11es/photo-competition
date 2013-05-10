<?php
/* 
 	Copyright (C) 2007-2008 Gilles Dubuc.
 
 	This file is part of photographycomp.com.
*/

require_once(dirname(__FILE__)."/../settings.php");
require_once(dirname(__FILE__)."/log.php");

class CacheException extends Exception {
}

class Cache {
	private static $started = false;
	private static $memcache = null;

	// Since the class is static we use that method to replace a constructor
	private static function initCheck() {
		global $MEMCACHE;
	
		// Create a new connection to memcached if there isn"t any	
		if (!Cache::$started) {
			Log::trace(__CLASS__, "*** starting ***");
			
			Cache::$memcache = new Memcache;
                        
                        $result = false;
                        
                        foreach ($MEMCACHE as $memcache_instance) {
                                if (!Cache::$memcache->addServer($memcache_instance["HOST"], $memcache_instance["PORT"]))
                                Log::critical(__CLASS__, "Cache missing on ".$memcache_instance["HOST"].":".$memcache_instance["PORT"]);
                                else $result = true;
                        }
                        
			if (!$result) throw new CacheException("Could not locate any memcache instance");

			Cache::$started = true;
			
			// Register a cleanup method that will be called automatically upon class destruction
			register_shutdown_function(array("Cache", "shutdown"));
		}
	}

	// Cleanup method, equivalent of a destructor
	public static function shutdown() {
		Log::trace(__CLASS__, "*** stopping ***");
		// Close the connection to memcached if it"s still alive
		if (Cache::$started && Cache::$memcache) {
			Cache::$memcache->close();
		}
	}

	// Retrieves an entry from the cache	
	public static function get($key) {
		global $MEMCACHE_PREFIX;
		Cache::initCheck();
		
		Log::trace(__CLASS__, "getting object with key=".$MEMCACHE_PREFIX.$key);
		$result = Cache::$memcache->get($MEMCACHE_PREFIX.$key);
		if (!$result && is_bool($result))
			throw new CacheException("This key is missing or has expired");
		return $result;
	}

	// Retrieves the memcached stats (performance, hit and misses, amount of entities currently cached, etc)	
	public static function getStats() {
		global $MEMCACHE;
		Cache::initCheck();
		
		Log::trace(__CLASS__, "getting stats");
		$result = Cache::$memcache->getStats();
		if (!$result && is_bool($result))
			throw new CacheException("Failed to obtain server stats");
		return $result;
	}
	
	// Assigns a cache entry to a given value/object, this can raise an exception if the entry is already set
	public static function set($key, $obj, $compressed=false, $duration=86400) {
		global $MEMCACHE_PREFIX;
		Cache::initCheck();
		
		Log::trace(__CLASS__, "setting object with key=".$MEMCACHE_PREFIX.$key);
		if (!Cache::$memcache->set($MEMCACHE_PREFIX.$key, $obj, $compressed, $duration))
			throw new CacheException("Failed to set value in the cache for key=".$key);
	}
	
	// Replace the value of an existing cache entry, this can raise an exception if the entry is not set yet
	public static function replace($key, $obj, $compressed=false, $duration=86400) {
		global $MEMCACHE_PREFIX;
		Cache::initCheck();
		
		Log::trace(__CLASS__, "replacing object with key=".$MEMCACHE_PREFIX.$key);
		if (!Cache::$memcache->replace($MEMCACHE_PREFIX.$key, $obj, $compressed, $duration))
			throw new CacheException("Failed to replace value in the cache for key=".$key);
	}
	
	// Sets or replaces a cache entry, works regardless of the entry being already defined or not
	public static function setorreplace($key, $obj, $compressed=false, $duration=86400) {
		global $MEMCACHE_PREFIX;
		Cache::initCheck();
		
		Log::trace(__CLASS__, "setting object with key=".$MEMCACHE_PREFIX.$key);
		if (!Cache::$memcache->set($MEMCACHE_PREFIX.$key, $obj, $compressed, $duration))
			Cache::replace($key, $obj, $compressed, $duration);
	}
	
	// Increments an integer value for a given key
	public static function increment($key) {
		global $MEMCACHE_PREFIX;
		Cache::initCheck();
		
		Log::trace(__CLASS__, "incrementing object with key=".$MEMCACHE_PREFIX.$key);
		if (!Cache::$memcache->increment($MEMCACHE_PREFIX.$key))
			throw new CacheException("Failed to increment value in the cache for key=".$key);
	}
	
	// Deletes a cache entry, raises an exception if the entry is not defined
	public static function delete($key) {
		global $MEMCACHE_PREFIX;
		Cache::initCheck();
		
		Log::trace(__CLASS__, "deleting object with key=".$MEMCACHE_PREFIX.$key);
		if (!Cache::$memcache->delete($MEMCACHE_PREFIX.$key))
			throw new CacheException("Failed to delete value in the cache for key=".$key);
	}
	
	// Flushed the whole cache, USE VERY CAUTIOUSLY, flushing the whole cache will slow down the website considerably
	public static function flush() {
		Cache::initCheck();
		
		Log::trace(__CLASS__, "Flushing cache");
		if (!Cache::$memcache->flush())
			throw new CacheException("Failed to flush the cache");
	}
}

?>