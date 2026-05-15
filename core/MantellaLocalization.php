<?php

/**
 * MantellaMVC - A PHP Framework For Web Applications
 *
 * @class    MantellaLocalization
 * @alias	LNG
 * @version    1.4
 * @author    Vasilij Olhov <vsl@inbox.lv>
 */
final class MantellaLocalization {

	/**
	 * Relative path to folder with locales
	 *
	 * @var string
	 */
    private static $localizationsPath = "localizations/";

	/**
	 * Default language
	 *
	 * @var string
	 */
    private static $defaultLanguage = 'en';

	/**
	 * Set of translated phrases for current language
	 *
	 * @var array
	 */
    private static $words = array();

	/**
	 * Current language
	 *
	 * @var array
	 */
    private static $currentLanguage = array();



	/**
	 * Initialize Localization class: define current language and read translations
	 *
	 * @param	string	$language
	 * @return	void
	 */
    public static function init( $language='' ) {

		if ( defined('DEFAULT_LANGUAGE') ) { self::$defaultLanguage = DEFAULT_LANGUAGE; }
        $language = ( preg_match("/^[a-zA-Z]{2}$/Us", $language ) == 1 ) ? strtolower($language) : self::$defaultLanguage;

		// === get localization from shared ===
		$shared_phrases = array();
		$filename = realpath(M_ROOT_PATH . ".." . DIRECTORY_SEPARATOR . "shared". DIRECTORY_SEPARATOR . self::$localizationsPath . $language.".ini" );
		if ( file_exists($filename) ) { $shared_phrases = CONF::parse_ini($filename); }

		// === get localization from shared ===
		$local_phrases = array();
		$filename = M_APP_PATH . self::$localizationsPath . $language.".ini";
		if ( file_exists($filename) ) { $local_phrases = CONF::parse_ini($filename); }

		// === if found, then merge shared and local localizations ===
		if ( count($shared_phrases)>0 || count($local_phrases)>0 ) {
			$keys = array_unique(array_merge( array_keys($local_phrases), array_keys($shared_phrases) ));
			foreach($keys as $k) {
				// if it is array, then cam merge
				if ( ( isset($shared_phrases[$k]) && is_array($shared_phrases[$k]) ) || ( isset($local_phrases[$k]) && is_array($local_phrases[$k]) ) ) {
					self::$words[$k] = array_merge(
						(isset($shared_phrases[$k]) ? $shared_phrases[$k] : array()),
						(isset($local_phrases[$k]) ? $local_phrases[$k] : array())
					);
				}
				else { // if not array, then define as string
					self::$words[$k] = isset($local_phrases[$k]) ? $local_phrases[$k] : $shared_phrases[$k];
				}
			}

			self::$currentLanguage = array(
				'id'    => $language,
				'name'  => self::get('language')
			);
		}

    }


	/**
	 * Manually initialize current language and set translations
	 *
	 * @param	string	$lang
	 * @param	string	$name
	 * @param	array	$words
	 * @return	void
	 */
	public static function manualInit( $lang='en', $name='English', $words=array() ) {
		self::$words = $words;
		self::$currentLanguage = array(
			'id'    => $lang,
			'name'  => $name
		);
	}



	/**
	 * Get translation(s) by keyword
	 *
	 * @param	string	$word
	 * @return	string|array
	 */
    public static function get( $word=null ) {
        if ( count(self::$words) == 0 ) { self::init(null); }
		if ( is_null($word) ) { return self::$words; }

		$def = explode(".",$word);
        if ( isset($def[1]) ) { // in section
        	$section = $def[0];
        	$var = $def[1];
        	return isset( self::$words[$section][$var]) ? self::$words[$section][$var] : $word;
        }

        return isset(self::$words[$word]) ? self::$words[$word] : $word;
    }




	/**
	 * Get current language as assoc array [id, name], or as value id $what defined
	 *
	 * @param	string	$what
	 * @return	array|string
	 */
    public static function getCurrent( $what = null ) {
        $lng = self::$currentLanguage;
        return ( $what ? ( !empty($lng[$what]) ? $lng[$what] : "" ) : $lng );
	}



	/**
	 * Get all available languages as array of assoc arrays [id, name]
	 *
	 * @param	void
	 * @return	array
	 */
    public static function languages( ) {
		$langs = array();
        $files = glob( M_APP_PATH . self::$localizationsPath . "*.ini" );
    	foreach($files as $file) {
    		$id = substr( basename($file) , 0 , -4);
    		$struct = CONF::parse_ini($file);
    		$langs[] = array(
				'id'	=> $id,
				'name'	=> isset($struct['language']) ? $struct['language'] : $id
			);
    	}
		return $langs;
	}



	/**
	 * Check if language is available
	 *
	 * @param	void
	 * @return	array
	 */
	public static function is_available( $lang ) {
		$files = glob( M_APP_PATH . self::$localizationsPath . "*.ini" );
		foreach($files as $file) {
			$id = substr( basename($file) , 0 , -4);
			if ( $id == strtolower($lang) ) { return true; }
		}
		return false;
	}


}

class_alias('MantellaLocalization', 'LNG');





