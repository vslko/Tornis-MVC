<?php
require_once('MantellaException.php');
require_once('MantellaController.php');


/**
 * MantellaMVC - A PHP Framework For Web Applications
 *
 * @class  MantellaApplication
 * @version  1.4
 * @author   Vasilij Olhov <vsl@inbox.lv>
 */
class MantellaApplication {


	/**
	 * Default controller for application.
	 *
	 * @var string
	 */
    private $_defaultController = null;


	/**
	 * Default action for application/
	 *
	 * @var string
	 */
    private $_defaultAction = null;


	/**
	 * Create a new application instance.
	 *
	 * @param  string  $controller
	 * @param  string  $action
     *
	 * @return void
	 */
    function __construct($controller="Index", $action="_") {
    	$this->_defaultController = $controller;
    	$this->_defaultAction = $action;
    }


    /**
     * Run application: find route, create controller and call routed method of controller.
     *
     * @throws MantellaException
     * @param void
     */
    public function run() {
        $this->includeObjects(realpath(M_ROOT_PATH.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."shared".DIRECTORY_SEPARATOR."models"));
        $this->includeObjects(M_APP_PATH."models");

        $this->includeObjects(realpath(M_ROOT_PATH.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."shared".DIRECTORY_SEPARATOR."collections"));
        $this->includeObjects(M_APP_PATH."collections");

        $this->includeObjects(M_APP_PATH."controllers");

        $this->includeObjects(realpath(M_ROOT_PATH.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."shared".DIRECTORY_SEPARATOR."mappers"));
        $this->includeObjects(M_APP_PATH."mappers");

    	$route = $this->getRoute();
        if (!preg_match('#^[A-Za-z0-9_-]+$#',$route['classname']) || !file_exists($route['classpath'])) {
      		throw new MantellaException("Controller file '".$route['classpath']."' not found." , 404);
      	}
      	require_once($route['classpath']);
        if (!class_exists($route['classname'])) {
        	throw new MantellaException("Controller class '".$route['classname']."' not found." , 404);
        }
        $controller = new $route['classname'];

        $gag = false;
        if (!method_exists($controller, $route['method'])) {
        	if ( !method_exists($controller, "gag") ) {
        		throw new MantellaException("Controller 'gag' or method '".$route['classname']."::".$route['method']."' not found." , 404);
        	}
            $gag = true; // action not exists, but gag exists
        }

        $act = strtolower( substr($route['method'],2, strlen($route['method'])) );
        $request = new MantellaRequest();
        $request->setPrefix( $route['prefix'] );
        $request->setAction( $act );
        $request->setController( $route['classname'] );

        // -- call controllers's init-method if exists
        if (method_exists($controller, "init")) {
        	call_user_func( array( $controller, "init"), $request );
        }

        if ($gag) { // --- then call gag-method
            call_user_func( array($controller, 'gag'), $request );
        }
        else { // --- then call action-method
            call_user_func( array($controller, $route['method']), $request );
        }

    }



	/**
	 * Parse URL and return url-prefix, controller class, action method.
	 *
	 * @param  void
	 * @return array
	 */
	private function getRoute() {

    	$ret = array(
    				  'prefix'		=> "",
    				  'classpath'	=> realpath(M_APP_PATH."controllers/")."/",
    				  'classname'	=> "",
    				  'method'		=> ""
    				);

    	$uri = $_SERVER['REQUEST_URI'];
        if ($uri[0] == '/') { $uri = substr($uri,1,strlen($uri)); }
        $q_sign_pos = strpos($uri, "?");
        if ( $q_sign_pos !== false ) { $uri = substr($uri,0, $q_sign_pos ); } // if slashes after ? (in url variables), then cut-out it
        $uri_parts = explode('/', $uri);

        if ( M_URL_PREFIX && count($uri_parts)>1 ) { $ret['prefix'] = array_shift($uri_parts); }

        $ret['method'] = array_pop($uri_parts);
     	$ret['method'] = ( strpos($ret['method'],"?") !== false ) ? substr($ret['method'],0,strpos($ret['method'],"?")) : $ret['method'];
     	$ret['method'] = "do" . ucfirst( ($ret['method']=="") ? $this->_defaultAction : str_replace(array("-","."),array("_","_dot_"),$ret['method']) );


        // if it's index-page without any actions and parameters
        if (count($uri_parts) == 0) {
            $uri_parts[0] = $this->_defaultController;
        }


        for ($i=0; $i<count($uri_parts); $i++) {
            if ($i == (count($uri_parts)-1)) {
    	       $ret['classpath'] .= ucfirst($uri_parts[$i]).".php";
               $ret['classname'] .= ucfirst($uri_parts[$i])."Controller";
            }
            else {
               $ret['classpath'] .= !empty($uri_parts[$i]) ? $uri_parts[$i]."/" : '';
               $ret['classname'] .= ucfirst($uri_parts[$i])."_";
            }
        }

        return $ret;
	}



	/**
	 * Include all php files in application_folder / $folder.
	 *
	 * @param  string $folder
	 * @return void
	 */
    private function includeObjects($folder) {
        $scan = glob( $folder . DIRECTORY_SEPARATOR . "*");
        foreach ($scan as $path) {
            $path = realpath($path);
            if (preg_match('/\.php$/', $path)) {
                require_once($path);
            }
            elseif (is_dir($path)) {
                $this->includeObjects($path);
            }
        }
    }



}



