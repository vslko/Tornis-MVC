<?php
/**
 * MantellaMVC - A PHP Framework For Web Applications
 *
 * @class    MantellaView
 * @alias    VIEW
 * @version    1.4
 * @author	Vasilij Olhov <vsl@inbox.lv>
 */
require_once('MantellaViewParser.php');

final class MantellaView {

    /**
	 * Set of variables
	 *
	 * @var array
	 */
	private static $_vars = array();

    /**
	 * Formed content
	 *
	 * @var string
	 */
    private static $_content = null;


	/**
	 * Define active template in VIEW
	 *
	 * @param	string	$name
	 * @return	void
	 */
	public static function template( $name ) {
		// search in local
		$filename = M_APP_PATH."views/".$name.".tmpl";
    	if (file_exists($filename)) {
    		self::$_content = file_get_contents($filename);
    	}
		else { // search in shared
			$filename = realpath(M_ROOT_PATH.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."shared".DIRECTORY_SEPARATOR."views/".$name.".tmpl");
			if (file_exists($filename)) {
				self::$_content = file_get_contents($filename);
			}
		}
	}

    /**
     * Return active template from VIEW
     *
     * @return	string
     */
	public static function get_template() {
        return self::$_content;
    }


	/**
	 * Check if template already assigned to VIEW
	 *
	 * @param	void
	 * @return	boolean
	 */
	public static function is_template_attached() {
		return (self::$_content) ? true : false;
	}


	/**
	 * Add variables to view
	 *
	 * @param	array|string	$names
	 * @param	string	$value
	 * @return	void
	 */
	public static function set( $names, $value=null ) {
    	if (is_array($names)) {
    		self::$_vars = array_merge(self::$_vars, $names);
    	}
    	else { self::$_vars[$names] = $value; }
	}


	/**
	 * Fetch variable by name from VIEW
	 *
	 * @param	string	$name
	 * @return	mixed
	 */
	public static function get( $name ) {
		return ( isset(self::$_vars[$name]) ? self::$_vars[$name] : False);
	}


    /**
     * Fetch all variables from VIEW
     *
     * @return	array
     */
    public static function get_all() {
        return self::$_vars;
    }


	/**
	 * Output rendered content
	 *
	 * @param	boolean	$terminate
     * @param	array $extra_variables
	 * @return	void
	 */
	public static function show($terminate=false, $extra_variables=array()) {
	    $content = self::render();

	    if (is_array($extra_variables) and count($extra_variables)) {
            $keys = array();
            $vals = array();
            foreach($extra_variables as $k => $v) {
                $keys[] = "{".$k."}";
                $vals[] = $v;
                $content = str_replace($keys, $vals, $content);
            }
        }

	    print $content;
        if ($terminate) { exit; }
	}


	/**
	 * Render content
	 *
	 * @param	void
	 * @return	string
	 */
    public static function render() {
        extract( self::$_vars );
        ob_start();
   	  	@eval(" ?> " . ( MantellaViewParser::parse( self::$_content ) ) ." <?php ");
   	  	$content = ob_get_contents();
  		ob_end_clean();
        return $content;
    }

}

class_alias('MantellaView', 'VIEW');