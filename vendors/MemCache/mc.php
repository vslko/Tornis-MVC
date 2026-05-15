<?php

/**
 * Memcache objekts
 */
class MC {
    /**
     * Memcache objekta instance
     */
	private static $mc = null;

    /**
     * Inicializācija - savienojums ar serveriem
     */
	public static function init(){ 
		self::$mc = new Memcache;
		self::$mc->connect('127.0.0.1', 11211);
    }

	public static function add($key, $value, $flag=0, $timeout=0){
        return self::$mc -> add($key, $value, $flag, $timeout);
	}
	
	public static function decrement($key, $value = 1){
		return self::$mc -> decrement($key, $value);
	}
	
	public static function delete($key){
        return self::$mc -> delete($key);
	}

	public static function get($key){
		if(empty($key)) return false;
		
		$key = self::cleanDuplicateKeys($key);
		
		if(empty($key)) return false;
        return self::$mc -> get($key);
	}
	
	public static function cleanDuplicateKeys($keys)
	{
		if(empty($keys)) return false;
		if(!is_array($keys)) return $keys;
		
		$storedKeys = $keys;
		$cleaned = array();
		
		foreach($keys as $idx => $key)
		{
			unset($storedKeys[$idx]);
			
			if(!in_array($key, $storedKeys))
			{
				$cleaned[$idx] = $key;
			}
		}
		
		return $cleaned;
	}

	public static function increment($key, $value=1){
		return self::$mc -> increment($key, $value);
	}

	public static function replace($key, $value, $flag=0, $timeout=0){
        return self::$mc -> replace($key, $value, $flag, $timeout);
	}

	public static function set($key, $value, $flag=0, $timeout=0){
        return self::$mc -> set($key, $value, $flag, $timeout);
	}


	public static function getConnection(){
      	return self::$mc;

    }
}
