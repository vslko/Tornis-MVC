<?php
/**
 * MantellaMVC - A PHP Framework For Web Applications
 *
 * @class    MantellaSession
 * @alias	AUTH
 * @version    1.2
 * @author    Vasilij Olhov <vsl@inbox.lv>
 */
final class MantellaSession {

	const SESSION_NAME = 'mantellaUserSession';

	/**
	 * Data of current session
	 *
	 * @var array
	 */
    private static $session = null;

	/**
	 * Preset for database authorization
	 *
	 * @var array
	 */
	private static $settings = array(  'dblink' => null,
									   'table'  => "users",
									   'fields' => array(),
									   'login'  => "login",
									   'secret' => "password",
									   'encode' => null); // null, md5, sha1 ..

	/**
	 * Last error message
	 *
	 * @var string
	 */
	private static $error = null;

	/**
	 * Flag, is class already initialized and ready for work
	 *
	 * @var boolean
	 */
    private static $initialized = false;





	/**
	 * Initialize MantellaSession class: init database authorization, renew session
	 *
	 * @param	void
	 * @return	void
	 */
	public static function init( $preset ) {
        self::$settings['dblink'] = DBM::get( trim($preset['connection']) );
        self::$settings['table'] = trim($preset['table']);

        $tmp = explode(",", trim($preset['fields']));
        for($i=0; $i<count($tmp); $i++) {
        	self::$settings['fields'][] = trim($tmp[$i]);
        }

        self::$settings['login'] = explode("|", trim($preset['login']) );
        self::$settings['secret'] = trim($preset['password']);
        self::$settings['encode'] = empty( $preset['encoding'] ) ? null : trim($preset['encoding']);

    	if (isset($_SESSION[self::SESSION_NAME])) {
    		self::$session = $_SESSION[self::SESSION_NAME];
    	}

        self::$initialized = TRUE;
	}


	/**
	 * Getter for session
	 *
	 * @param	string	$name
	 * @return	mixed
	 */
	public static function get($name) {
		// check initailization
        if (!self::$initialized) { self::init(); }

		return ( isset(self::$session[$name]) ? self::$session[$name] : null );
	}


	/**
	 * Setter for session
	 *
	 * @param	string	$name
	 * @param	mixed	$value
	 * @return	void
	 */
    public static function set($name, $value) {
    	// check initailization
        if (!self::$initialized) { self::init(); }
    	
        self::$session[$name] = $value;
    	$_SESSION[self::SESSION_NAME] = self::$session;
    }



	/**
	 * Cheack authorization in database
	 *
	 * @param	string	$login
	 * @param	string	$password
	 * @return	boolean
	 */
    public static function login( $login, $secret ) {
        // check initailization
        if (!self::$initialized) { self::init(); }
        // erase previously stored session
        self::logout();

        // check database
    	if (!self::$settings['dblink']) { return self::setError("Session storage not defined"); }
        // encode password
        if (self::$settings['encode']) {
        	if (!function_exists(self::$settings['encode']) ) { return self::setError("Password encode function '".self::$settings['encode']."' not found"); }
     		$secret = call_user_func_array( self::$settings['encode'], array($secret) );
     	}
        // formation sql-query
        $sql = null;
		for ($i=0; $i<count(self::$settings['fields']); $i++) {
			$sql .= (($sql) ? ", " : " ") . self::$settings['fields'][$i];
		}
        $sql_login = null;
        foreach(self::$settings['login'] as $item) {
            $sql_login .= (($sql_login) ? ' or ': '') . $item. "='" . addslashes($login) . "'";
        }
        $sql = "select " . $sql . " from " . self::$settings['table'] . " where " . self::$settings['secret'] . "='" . addslashes($secret) . "'";
        $sql .= " and (" . $sql_login . ")";
        $res = self::$settings['dblink']->execSQL($sql);
		if ( is_array($res) && (count($res)==1) ) {
            foreach($res[0] as $name => $value) { self::set($name, $value); }
            self::set('_logged', @date("d.m.Y H:i"));
            $_SESSION[self::SESSION_NAME] = self::$session;
			return self::setError(null);
		}
		else {
			return self::setError("Invalid login or password");
		}
    }


	/**
	 * Cheack if authorization was early and return auth-data
	 *
	 * @param	string	$login
	 * @param	string	$password
	 * @return	array
	 */
    public static function logged() {
    	// check initailization
        if (!self::$initialized) { self::init(); }

    	return (self::$session);
    }


	/**
	 * Return last error and clear it from class
	 *
	 * @param	void
	 * @return	string
	 */
    public static function error() {
        $err = self::$error;
        self::$error = null;
        return $err;
    }


	/**
	 * Erase session and auth-data
	 *
	 * @param	void
	 * @return	void
	 */
    public static function logout() {
    	self::$session = null;
    	unset($_SESSION[self::SESSION_NAME]);
    }


	/**
	 * Remember error message in class
	 *
	 * @param	string	$message
	 * @return	boolean
	 */
	protected static function setError($message = null) {
		 self::$error = $message;
		 return (is_null($message));
	}


}
