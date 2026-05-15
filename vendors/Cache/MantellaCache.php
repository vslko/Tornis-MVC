<?php
/**
 * MantellaMVC - A PHP Framework For Web Applications
 *
 * @class	MantellaCache
 * @alias	CACHE
 * @version	1.2
 * @author	Vasilij Olhov <vsl@inbox.lv>
 */
final class MantellaCache {

	/**
	 * Path to store cache-files.
	 *
	 * @var string
	 */
    private static $PATH = null;

	/**
	 * Time to live of cache file in seconds.
	 *
	 * @var integer
	 */
    private static $TIME = 86400; // 24 hours

	/**
	 * Prefix in filename for cache-files
	 *
	 * @var string
	 */
    private static $PREFIX = "m-cached-";

	/**
	 * Flag, is class already initialized and ready for work
	 *
	 * @var boolean
	 */
    private static $_ready = false;



	/**
	 * Initialize MantellaCache class
	 *
	 * @param	string	$path_to_cache
	 * @param	integer	$ttl_time
	 * @param	string	$filenames_prefix
	 * @return	void
	 */
    public static function init( $path=null, $time=null, $prefix=null ) {
   		self::$PATH = $path ? $path : sys_get_temp_dir();
    	self::$TIME = $time ? (int)$time : self::$TIME;
    	self::$PREFIX = $prefix ? $prefix : self::$PREFIX;
    	self::$_ready = true;
    }


	/**
	 * Check if class is initialized already
	 *
	 * @param	void
	 * @return	void
	 */
    private static function checkInit() {
        if (!self::$_ready) {
	    	self::init();
	    }
    }


	/**
	 * Defines full path to cache-file
	 *
	 * @param	string	$filename
	 * @return	string
	 */
    private static function getCacheFile( $name ) {
    	return self::$PATH . '/' . self::$PREFIX . $name;
    }


	/**
	 * Save information in cache-file
	 *
	 * @param	string	$key
	 * @param	mixed	$value
	 * @return	void
	 */
    public static function set( $name, $value ) {
        self::checkInit();

        $filename = self::getCacheFile( $name );

        if (file_exists($filename)) { unlink($filename); }
        file_put_contents ($filename, serialize($value) );
    }


	/**
	 * Check if ttl is valid and return data from cache file or FALSE if data not found or ttl expired
	 *
	 * @param	string	$key
	 * @param	integer	$ttl_time
	 * @return	mixed|boolean
	 */
    public static function get( $name , $newTime=null ) {
        self::checkInit();

        $result = false;

		$filename = self::getCacheFile( $name );
		$termTime = $newTime ? (int)$newTime : self::$TIME;

		if ( file_exists($filename) ) {			if ( time()-$termTime < filemtime($filename) ) {				$result = unserialize( file_get_contents($filename) );			}
			else { unlink($filename); }
		}
		return $result;
    }


	/**
	 * Clear data in cache by key
	 *
	 * @param	string	$key
	 * @return	boolean
	 */
	public static function clear( $name ) {
	    self::checkInit();

        $filename = self::getCacheFile( $name );
        echo $filename;
        if (file_exists($filename)) {
        	return unlink($filename);
        }
        return true;
	}


}
