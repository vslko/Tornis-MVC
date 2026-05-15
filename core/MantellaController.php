<?php

/**
 * MantellaMVC - A PHP Framework For Web Applications
 *
 * @class    MantellaController
 * @version    1.4
 * @author    Vasilij Olhov <vsl@inbox.lv>
 */

abstract class MantellaController {


	/**
	 * Create a new controller instance.
	 *
	 * @param  void
	 * return void
	 */
  	function __construct()  {
    	// do nothing
  	}


	/**
	 * Get model instance by name
	 *
	 * @param string $name
	 * @return MantellaModel
	 * @throws MantellaException
	 */
    public function getModel($name) {
		$class_name = ucfirst($name."Model");
		if ( class_exists($class_name) ) { return new $class_name; }
		else { throw new MantellaException("Model class '".$class_name."' not found." , 500); }
    }


	/**
	 * Get collection instance by name
	 *
	 * @param  string $name
	 * @return MantellaCollection
	 * @throws MantellaException
	 */
	public function getCollection($name) {
		$class_name = ucfirst($name . "Collection");
		if (class_exists($class_name)) {
			$collection = new $class_name;
			if (!($m = $this->getModel($collection->getModelName()))) {
				throw new MantellaException("In collections '" . $class_name . " not found model class '" . $collection->getModelName() . "'.", 500);;
			}
			$collection->setModelInstance($m);
			return $collection;
		}
		else {
			throw new MantellaException("Collection class '" . $class_name . "' not found.", 500);
		}
	}

     /**
	 * Get controller instance by name
	 *
	 * @param  string $name
	 * @return MantellaController
	 * @throws MantellaException
	 */
    public function getController($name) {
    	$class_name = ucfirst($name."Controller");
    	if (!class_exists($class_name)) {
			throw new MantellaException("Controller class '".$class_name."' not found." , 500);
		}
    	return ( new $class_name );
    }



	/**
	 * Flush any text message into error log / debugging case
	 *
	 * @param  string	$message
	 * @return void
	 */
	public function log( $message ) {
		$msg = gmdate("d.m.Y H:i:s O") . "   Addr[".$_SERVER["REMOTE_ADDR"]."]   URL[".$_SERVER["SERVER_NAME"].$_SERVER['REQUEST_URI']."]   Message:[".$message."]\n";
		if ( !defined('M_ERR_PATH') || !($handler = @fopen( M_ERR_PATH , "a")) ) {
			return false;
		}
		@fwrite( $handler, $msg );
		@fclose( $handler );
	}


}

