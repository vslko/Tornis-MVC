<?php

/**
 * MantellaMVC - A PHP Framework For Web Applications
 *
 * @class	MantellaConfig
 * @alias	CONF
 * @version	1.4
 * @author	Vasilij Olhov <vsl@inbox.lv>
 */

final class MantellaConfig {

    /**
	 * Set of shared variables as pair key-value.
	 *
	 * @var array
	 */
    private static $_vars = array();


	/**
	 * Set of paths as pair key-value.
	 *
	 * @var array
	 */
	private static $_paths = array();


    /**
	 * Set of parameters for vendors initialization
	 *
	 * @var array
	 */
    private static $_vendor_params = array();


	/**
	 * Initialize Config class: read config file and define global variables
	 *
	 * @param	string	$config_file
	 * @param	string	$root_path
	 * @return	void
	 */
    public static function init($config_file, $root_path) {

  		$cfg = self::parse_ini($config_file);

		// ==== Global definitions ====
		define('M_ROOT_PATH', $root_path ); // path from web root directory
		self::$_paths['M_ROOT_PATH'] = $root_path;


		// === Init logs and errors output ===
        if ( !empty( $cfg['globals']['PHP_LOG_PATH'] ) ) {
            ini_set("log_errors", 'On');
            ini_set("error_log", self::isAbsolutePath( $cfg['globals']['PHP_LOG_PATH'] )
                                 ? $cfg['globals']['PHP_LOG_PATH']
                                 : M_ROOT_PATH.$cfg['globals']['PHP_LOG_PATH']
            );
        }

        ini_set( // switch error displaying on page
			'display_errors',
			empty( $cfg['globals']['ERROR_DISPLAY'] ) ? 'Off' : strval( $cfg['globals']['ERROR_DISPLAY'] )
		);


        define( // path of errors log-file
        	'M_ERR_PATH',
			( !isset($cfg['globals']['ERROR_LOG_PATH']) || empty($cfg['globals']['ERROR_LOG_PATH']) )
				? null
				: ( self::isAbsolutePath( $cfg['globals']['ERROR_LOG_PATH'] )
				    ? $cfg['globals']['ERROR_LOG_PATH'] 				// already absolute path
				    : M_ROOT_PATH.$cfg['globals']['ERROR_LOG_PATH'] ) 	// relative path to absolute
        );
		self::$_paths['M_ERR_PATH'] = M_ERR_PATH;


        define(
        	'M_APP_PATH',
			empty( $cfg['globals']['M_APP_PATH'] ) ? M_ROOT_PATH.'app/' : M_ROOT_PATH.$cfg['globals']['APP_PATH']
        );
		self::$_paths['M_APP_PATH'] = M_APP_PATH;


		define(
			'M_WEB_PATH',
			empty( $cfg['globals']['WEB_FOLDER_PATH'] ) ? null : $cfg['globals']['WEB_FOLDER_PATH']
		);
		self::$_paths['M_WEB_PATH'] = M_WEB_PATH;


        define(
        	'M_SITE_NAME',
			empty( $cfg['globals']['SITE_NAME'] ) ? 'Mantella Site' : $cfg['globals']['SITE_NAME']
        );


		define(
        	'M_ADMIN_EMAIL',
			empty( $cfg['globals']['ADMIN_EMAIL'] ) ? null : $cfg['globals']['ADMIN_EMAIL']
        );


      	define(
        	'M_URL_PREFIX',
			empty( $cfg['globals']['URL_PREFIX'] ) ? null : $cfg['globals']['URL_PREFIX']
        );


        if ( !$cfg['globals']['BASE_URL'] ) {
            $_path = str_replace('\\', '/', M_ROOT_PATH);
            $_proto = $_SERVER['SERVER_PORT'] != 443 ? 'http://' : 'https://';
            $_port = $_SERVER['SERVER_PORT'] != 443 && $_SERVER['SERVER_PORT'] != 80 ? ':' . $_SERVER['SERVER_PORT'] : '';
            $cfg['globals']['BASE_URL'] = strtolower($_proto . $_SERVER['SERVER_NAME'].$_port.preg_replace('/^[\w:\/]*'.str_replace('/', '\/', $_SERVER["DOCUMENT_ROOT"]).'/i', '', $_path));
		}
        define('M_BASE_URL', $cfg['globals']['BASE_URL'] );
		self::$_paths['M_BASE_URL'] = M_BASE_URL;


        // ==== Custom definitions ====
		foreach( $cfg['definitions'] as $name => $value ) {
	        define( $name , $value );
		}


		// ==== Read databases and init DBManager ====
		$databases = array();
		foreach( $cfg['databases'] as $name => $value) {
			$db = explode(".",$name);
			if ( !isset( $databases[$db[0]] ) ) {
				$databases[$db[0]] = array(
					'driver'	=> "unknown",
					'host'		=> "localhost",
					'port'		=> null,
					'database'	=> "base",
					'username'	=> null,
					'password'	=> null,
					'charset'	=> null,
					'prefix'	=> null
				);
			}
			$databases[ $db[0] ][ $db[1] ] = $value;
		}
        if (class_exists('DBM')) { DBM::init( $databases ); }

        // ==== Vendors ====
		foreach( $cfg['vendors'] as $name => $params ) {
            self::set( "vendor_".$name, $params);
			$init_path = realpath(M_ROOT_PATH . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "vendors"  . DIRECTORY_SEPARATOR . $name."/init.php" );
			if(file_exists($init_path)) { include_once($init_path); }
		}

    }




	/**
	 * Set shared variable
	 *
	 * @param	string	$name
	 * @param	mixed	$value
	 * @return	void
	 */
    public static function set( $name, $value) {
    	self::$_vars[$name] = $value;
    }

	/**
	 * Get shared variable value
	 *
	 * @param	string	$name
	 * @return	mixed
	 */
    public static function get( $name ) {
        return empty(self::$_vars[$name]) ? null : self::$_vars[$name];
    }

	/**
	 * Remove shared variable
	 *
	 * @param	string	$name
	 * @return	void
	 */
	public static function clear( $name ) {
		if ( isset(self::$_vars[$name]) ) {
			unset( self::$_vars[$name] );
		}
	}





	/**
	 * Set path variable
	 *
	 * @param	string	$name
	 * @param	mixed	$value
	 * @return	void
	 */
	public static function setPath( $name, $value) {
		self::$_paths[$name] = $value;
	}

	/**
	 * Get path variable
	 *
	 * @param	string	$name
	 * @return	mixed
	 */
	public static function getPath( $name ) {
		return empty(self::$_paths[$name]) ? null : self::$_paths[$name];
	}

	/**
	 * Get all path variables
	 *
	 * @return	array
	 */
	public static function getPaths() {
		return self::$_paths;
	}

	/**
	 * Define if path is absolute
	 *
	 * @param	string	$path
	 * @return	boolean
	 */
    private static function isAbsolutePath( $path = ' ' ) {
    	return (boolean)($path[0]==DIRECTORY_SEPARATOR || $path[0]=='.');
    }







	/**
	 * Override standart parse_inn_file, which may be in php.ini "disabled_function" parameter
	 *
	 * @param	string	$filename
	 * @return	array
	 */
	public static function parse_ini($filename) {
		$result  = array();
		$section = null;

		$lines = explode("\n" , file_get_contents($filename) );
		foreach ($lines as $i => $line) {
			// erase spaces
			$line = trim($line);

			// empty line or comment
			if ( strlen($line)==0 || $line[0]==';' ) { continue; }


			// it's section begin
			if ($line[0] == '[') {
				$section = substr( substr($line,1) , 0 , -1);
				$result[$section] = array();
				continue;
			}

			$name = trim( substr($line,0, strpos($line, "=") ) );
			$value = trim( substr($line, strpos($line, "=")+1 ) );
			if ($value[0] == '"') { // string in quotes
				$value = substr( substr($value,1), 0, -1);
			}
			else {
				if ( strtoupper($value) == 'NULL') { $value = null; } // null value
				elseif ( strtoupper($value)=='FALSE' || strtoupper($value)=='NO' ) { $value = false; } // boolean false
				elseif ( strtoupper($value)=='TRUE' || strtoupper($value)=='YES' ) { $value = true; } // boolean true
				elseif ( is_int($value) ) { $value = (int)$value; } // integer
				elseif ( is_float($value) ) { $value = (float)$value; } // float
				else { $value = (string)$value; } // string without quotes
			}

			if ($section) { $result[$section][$name] = $value; }
			else { $result[$name] = $value; }
		}

		return $result;
	}

}

class_alias('MantellaConfig', 'CONF');