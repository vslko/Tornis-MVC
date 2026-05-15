<?php
/**
 * MantellaMVC - A PHP Framework For Web Applications
 *
 * @class    MantellaException
 * @version    1.4
 * @author    Vasilij Olhov <vsl@inbox.lv>
 */

class MantellaException extends Exception {

	/**
	 * Reference number, unique number of exception
	 *
	 * @var string
	 */
    private $REFNUM = "0000000000000";

	/**
	 * Possible exception codes (http codes)
	 *
	 * @var array
	 */
	private $codes = array(
                        // Information Codes
                        '100' => "Continue",
                        '101' => "Switching Protocols",
                        // Success Codes
                        '200' => "OK",
                        '201' => "Created",
                        '202' => "Accepted",
                        '203' => "Non-Authoritative Information",
                        '204' => "No Content",
                        '205' => "Reset Content",
                        '206' => "Partial Content",
                        // Redirection Codes
                        '300' => "Multiple Choices",
                        '301' => "Moved Permanently",
                        '302' => "Found",
                        '303' => "See Other",
                        '304' => "Not Modified",
                        '305' => "Use Proxy",
                        '307' => "Temporary Redirect",
                        // Client Error Codes
                        '400' => "Bad Request",
                        '401' => "Unauthorized",
                        '402' => "Payment Required",
                        '403' => "Forbidden",
                        '404' => "Not Found",
                        '405' => "Method Not Allowed",
                        '406' => "Not Acceptable",
                        '407' => "Proxy Authentication Required",
                        '408' => "Request Timeout",
                        '409' => "Conflict",
                        '410' => "Gone",
                        '411' => "Length Required",
                        '412' => "Precondition Failed",
                        '413' => "Request Entity Too Large",
                        '414' => "Request-URI Too Large",
                        '415' => "Unsupported Media Type",
                        '416' => "Requested Range Not Satisfiable",
                        '417' => "Expectation Failed",
                        // Server Error Codes
                        '500' => "Internal Server Error",
                        '501' => "Not Implemented",
                        '502' => "Bad Gateway",
                        '503' => "Service Unavailable",
                        '504' => "Gateway Timeout",
						'505' => "HTTP Version not supported"
 	);


	/**
	 * Create a new exception
	 *
	 * @param  string $message
	 * @param  string $code
	 * @return void
	 */
    public function __construct($m, $c) {    	$this->REFNUM = @date('smdHi').rand(100, 999);
    	parent::__construct($m, $c);
    }


	/**
	 * Return generated reference number
	 *
	 * @param  void
	 * @return string
	 */
    public function getRef() {    	return $this->REFNUM;
    }


	/**
	 * Return text of error code
	 *
	 * @param  void
	 * @return string
	 */
    public function getHTTPCause() {
       $code = (string)$this->getCode();
       return (isset($this->codes[$code]) ? $this->codes[$code] : "Unknown");
    }


	/**
	 * Forms and return HTTP header with error cause
	 *
	 * @param  void
	 * @return string
	 */
    public function getHttpHeader() {
      	$code = (string)$this->getCode();
      	return "HTTP/1.0 " . $this->getCode() . " " . $this->getHTTPCause();
    }


	/**
	 * Return exception full description
	 *
	 * @param  void
	 * @return array
	 */
	public function getFullError() {	    $error = array(
    					'refnum' 	=> $this->getRef(),
    					'code' 		=> $this->getCode(),
    					'date'		=> gmdate("d.m.Y"),
    					'time'		=> gmdate("H:i:s"),
    					'addr'		=> $_SERVER["REMOTE_ADDR"],
    					'url'		=> $_SERVER["SERVER_NAME"].$_SERVER['REQUEST_URI'],
    					'descr'		=> $this->getMessage(),
    					'cause'		=> $this->getHTTPCause(),
    					'admin'		=> ( (defined('M_ADMIN_EMAIL')) ? M_ADMIN_EMAIL : null ),
    	);		return $error;
	}



	/**
	 * Forms and return message for log-file
	 *
	 * @param  void
	 * @return string
	 */
    private function getLogString() {
    	return gmdate("d.m.Y H:i:s O") . "    REF[".$this->getRef()."]    Addr[".$_SERVER["REMOTE_ADDR"]."]   URL[".$_SERVER["SERVER_NAME"].$_SERVER['REQUEST_URI']."]   Error:[".$this->getMessage()."]   File:[".$this->getFile()." (line ".$this->getLine().")]\n";
    }



	/**
	 * Write exeption into log-file
	 *
	 * @param  void
	 * @return boolean
	 */
	public function logException() {
   		if ( !defined('M_ERR_PATH') || !($handler = @fopen( M_ERR_PATH , "a")) ) {
   			return false;
   		}
   		@fwrite( $handler, $this->getLogString() );
   		@fclose( $handler );
   		return true;	}

}