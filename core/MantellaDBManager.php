<?php
/**
 * MantellaMVC - A PHP Framework For Web Applications
 *
 * @class    MantellaDBManager
 * @alias	DBM
 * @version    1.4
 * @author	Vasilij Olhov <vsl@inbox.lv>
 */

require_once('MantellaDB.php');

final class MantellaDBManager {

	/**
	 * Set of database connections.
	 *
	 * @var array
	 */
	private static $DBS = array();


	/**
	 * Class initialization, creates set of database connections
	 *
	 * @param  array	$links
	 * @return void
	 */
    public static function init( $links ) {
        foreach($links as $id => $params) {
			self::$DBS[$id] = new MantellaDB( $params );
    	}
    }

	/**
	 * Return database connection by link name (in configuratuion defined), or FALSE if connection not found or not connected
	 *
	 * @param  atring	$dbname
	 * @return MantellaDB|boolean
	 */
    public static function get($dbname) {
		if ( isset(self::$DBS[$dbname]) ) {
			if (!self::$DBS[$dbname]->connect()) {
				$err = self::$DBS[$dbname]->getLastError();
				throw new MantellaException( "#".$err['code'].": ".$err['text'] , 500);
				return null;
			}
    		return self::$DBS[$dbname];
		}
    	return false;
    }

}
class_alias('MantellaDBManager', 'DBM');