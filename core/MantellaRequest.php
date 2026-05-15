<?php

/**
 * MantellaMVC - A PHP Framework For Web Applications
 *
 * @class    MantellaRequest
 * @version    1.4
 * @author    Vasilij Olhov <vsl@inbox.lv>
 */

class MantellaRequest {

	/**
	 * Url-prefix value
	 *
	 * @var string
	 */
    private $_PREFIX = null;

	/**
	 * Controller name
	 *
	 * @var string
	 */
    private $_CONTROLLER = null;

	/**
	 * Controller action methode
	 *
	 * @var string
	 */
    private $_ACTION = null;

	/**
	 * Create a new request instance.
	 *
	 * @param void
	 */
	public function __construct() {
		// do nothing
	}

	/**
	 * Setter for url-prefix
	 *
	 * @param  string
	 * @return void
	 */
	public function setPrefix($prefix) {
		$this->_PREFIX = $prefix;
	}

	/**
	 * Getter for url-prefix
	 *
	 * @param  void
	 * @return string
	 */
	public function getPrefix() {
		return $this->_PREFIX;
	}

	/**
	 * Setter for controller name
	 *
	 * @param  string
	 * @return void
	 */
	public function setController($controller) {
		$this->_CONTROLLER = substr($controller, 0, -10); // cut word "Controller" at end
	}

	/**
	 * Getter for controller name
	 *
	 * @param  void
	 * @return string
	 */
	public function getController() {
		return $this->_CONTROLLER;
	}

	/**
	 * Setter for controller action method
	 *
	 * @param  string
	 * @return void
	 */
	public function setAction($action) {
		$this->_ACTION = $action;
	}

	/**
	 * Getter for controller action method
	 *
	 * @param  void
	 * @return string
	 */
	public function getAction() {
		return $this->_ACTION;
	}

	/**
	 * Get value of GET or POST variable in HTTP request
	 *
	 * @param  string	$varname
	 * @param  string	$defaultValue
	 * @param  boolean	$canValueNull
	 * @return string
	 */
	public function getVar($varname, $defaultValue=null, $canValueNull=false) {
 		$ret = null;
 		$ret = isset($_POST[$varname])
					? $_POST[$varname]
					: ( isset($_GET[$varname]) ? $_GET[$varname] : NULL );
		if ( ( is_null($ret) || (is_string($ret) && strlen($ret)==0) ) && $canValueNull==false ) { $ret = $defaultValue; }

		return $ret;
	}


	/**
	 * Set value of POST or GET variable
	 *
	 * @param  string	$name
	 * @param  string	$value
	 * @param  string	$type
	 * @return void
	 */
	public function setVar($name, $value, $type='POST') {
		if ( strtoupper($type)=="POST" ) { $_POST[$name] = $value; }
		else { $_GET[$name] = $value; }
	}


	/**
	 * Get value of POST variable in HTTP request
	 *
	 * @param  string	$varname
	 * @param  string	$defaultValue
	 * @param  boolean	$canValueNull
	 * @return string
	 */
	public function getPost($varname, $defaultValue=null, $canValueNull=false) {
		return ($this->getVarType($varname) === "POST")
					? $this->getVar($varname, $defaultValue, $canValueNull)
					: ($canValueNull ? NULL : $defaultValue);
	}

	/**
	 * Get value of GET variable in HTTP request
	 *
	 * @param  string	$varname
	 * @param  string	$defaultValue
	 * @param  boolean	$canValueNull
	 * @return string
	 */
	public function getGET($varname, $defaultValue=null, $canValueNull=false) {
		return ($this->getVarType($varname) === "GET")
					? $this->getVar($varname, $defaultValue, $canValueNull)
					: ($canValueNull ? NULL : $defaultValue);
	}


	/**
	 * Get all valuef of POST anf GET variables in HTTP request
	 *
	 * @param  void
	 * @return array
	 */
	public function getVars() {
       return array_merge($_POST, $_GET);
	}


	/**
	 * Return is POST or GET variable by name
	 *
	 * @param  string	$varname
	 * @return string
	 */
    public function getVarType($varname) {
    	return array_key_exists($varname,$_POST) ? 'POST'
			   									 : ( array_key_exists($varname,$_GET) ? 'GET' : 'UNKNOWN' ) ;
    }


	/**
	 * Return is there Ajax request
	 *
	 * @param  void
	 * @return boolean
	 */
    public function isAjax() {
    	return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ? true : false;
    }



    /**
     * Check if bearer token in header is valid
     *
     * @param  string	$valid_token
     * @return boolean
     */
    public function is_bearer_token_valid($valid_token) {
        $token = "";
        if (isset($_SERVER['Authorization'])) { $token = trim($_SERVER["Authorization"]); }
        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { $token = trim($_SERVER["HTTP_AUTHORIZATION"]); }
        elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) { $token = trim($requestHeaders['Authorization']); }
        }
        $token = strtolower(str_replace("Bearer ", "", $token));
        return ($token == strtolower($valid_token));
    }



	/**
	 * Send JSON package with SUCCESS flag to client
	 *
	 * @param  mixed	$data
	 * @param  string	$message
	 * @return void
	 */
	public function replyJson( $data , $message="" ) {
		$reply =array( 'success'=>true, 'message'=>$message, 'data'=>$data );
		$this->sendJson( $reply, 200);
	}

	/**
	 * Send JSON package with FAILED flag to client
	 *
	 * @param  string	$errorMessage
	 * @return void
	 */
   	public function replyJsonError($errorMessage, $http_code=200) {
		$reply = array( 'success'=>false, 'message'=>$errorMessage );
		$this->sendJson( $reply, $http_code);
	}


	/**
	 * Output JSON package and terminate application
	 *
	 * @param  mixed	$reply
     * @param  integer  $http_code
	 * @return void
	 */
    public function sendJson( $reply, $http_code=200) {
        header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Sun, 01 Jan 2012 01:01:01 GMT');
		header('Content-type: application/json');
        switch ($http_code) {
            case 100: $http_text = 'Continue'; break;
            case 101: $http_text = 'Switching Protocols'; break;
            case 200: $http_text = 'OK'; break;
            case 201: $http_text = 'Created'; break;
            case 202: $http_text = 'Accepted'; break;
            case 203: $http_text = 'Non-Authoritative Information'; break;
            case 204: $http_text = 'No Content'; break;
            case 205: $http_text = 'Reset Content'; break;
            case 206: $http_text = 'Partial Content'; break;
            case 300: $http_text = 'Multiple Choices'; break;
            case 301: $http_text = 'Moved Permanently'; break;
            case 302: $http_text = 'Moved Temporarily'; break;
            case 303: $http_text = 'See Other'; break;
            case 304: $http_text = 'Not Modified'; break;
            case 305: $http_text = 'Use Proxy'; break;
            case 400: $http_text = 'Bad Request'; break;
            case 401: $http_text = 'Unauthorized'; break;
            case 402: $http_text = 'Payment Required'; break;
            case 403: $http_text = 'Forbidden'; break;
            case 404: $http_text = 'Not Found'; break;
            case 405: $http_text = 'Method Not Allowed'; break;
            case 406: $http_text = 'Not Acceptable'; break;
            case 407: $http_text = 'Proxy Authentication Required'; break;
            case 408: $http_text = 'Request Time-out'; break;
            case 409: $http_text = 'Conflict'; break;
            case 410: $http_text = 'Gone'; break;
            case 411: $http_text = 'Length Required'; break;
            case 412: $http_text = 'Precondition Failed'; break;
            case 413: $http_text = 'Request Entity Too Large'; break;
            case 414: $http_text = 'Request-URI Too Large'; break;
            case 415: $http_text = 'Unsupported Media Type'; break;
            case 500: $http_text = 'Internal Server Error'; break;
            case 501: $http_text = 'Not Implemented'; break;
            case 502: $http_text = 'Bad Gateway'; break;
            case 503: $http_text = 'Service Unavailable'; break;
            case 504: $http_text = 'Gateway Time-out'; break;
            case 505: $http_text = 'HTTP Version not supported'; break;
            default: $http_text = 'OK'; break;
        }
        header( (!empty($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0').' '.$http_code.' '.$http_text);
        print( json_encode($reply) );
        exit;
    }


	/**
	 * Output text and terminate application
	 *
	 * @param  string	$content
	 * @param  string	$contentType
	 * @return void
	 */
	public function reply( $content, $contentType = null ) {
		if ($contentType) { header('Content-type: '.$contentType.';'); }
		print( $content );
		exit;
	}


	/**
	 * Output dump of object
	 *
	 * @param  mixed	$object
	 * @param  boolean	$terminate
	 * @return void
	 */
	public function dump( $object, $terminate=false ) {
		echo "<pre style='padding:12px; padding-right:24px; margin:12px; border:1px solid #ccc; border-radius: 8px; display:inline-block; font-size:12px; font-family:Tahoma; background-color:#FAFAFA; color:#555;'>\n" .
			   print_r( $object , true) .
			 "</pre>";
		if ($terminate) { exit; }
	}


	/**
	 * Redirection
	 *
	 * @param  string	$url
	 * * @param  boolean	$permanent
	 * @return void
	 */
    public function redirect( $url, $permanent=false ) {
    	header("Location: ".$url, true, ($permanent ? 301 : 302) );
    	exit;
    }


	/**
	 * Generate headers for file downloading, output it and terminate application
	 *
	 * @param  string	$filepath
	 * @param  string	$filename
	 * @return void
	 */
	public function download( $filepath, $filename=null ) {
    	if (!file_exists($filepath)) { $this->error(404, "File not exists"); }
    	header("Expires: 0");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
		header("Content-Type: application/octet-stream;");
		header("Content-Transfer-Encoding: binary");
		header('Content-length: '.filesize($filepath));
		header('Content-disposition: attachment; filename="' . ( $filename ? $filename : basename($filepath) ) . '"' );
		ob_clean();
    	flush();
    	readfile($filepath);
    	exit;
	}


	/**
	 * Generate table in excel file for downloading, output it and terminate application
	 *
	 * @param  array	$headers
	 * @param  array of arrays	$data
	 * @param  string	$filename
	 * @return void
	 */
	public function excelReport( $headers, $data, $filename="report.xls" ) {
		header('Content-Description: File Transfer');
		header ("Content-Type: application/vnd.ms-excel");
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Content-Transfer-Encoding: binary');
		header("Accept-Ranges: bytes");
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		ob_clean();
    	flush();
		printf( '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">' . "\n".
			    '<head>' . "\n".
			    '<title>Excel report</title>' . "\n" .
				'<style type="text/css">' . "\n" .
				'table { mso-displayed-decimal-separator:"\."; mso-displayed-thousand-separator:"\,"; }' . "\n" .
				'body { margin: 0px; font-size: 12px; font-family: Arial, Sans-Serif, sans-serif; color:black; }' . "\n".
				'</style>' . "\n".
				'<META HTTP-EQUIV="Content-Type" Content="application/vnd.ms-excel; charset=UTF-8">' . "\n".
				'<style>' . "\n".
				'@page {' . "\n".
				'mso-page-orientation:landscape;' . "\n".
				'margin:.25in .25in .5in .25in;' . "\n".
				'mso-header-margin:.5in;' . "\n".
				'mso-footer-margin:.25in;' . "\n".
				'mso-footer-data:"&R&P of &N";' . "\n".
				'mso-horizontal-page-align:center;' . "\n".
				'mso-vertical-page-align:center;' . "\n".
				'}' . "\n".
				'br { mso-data-placement:same-cell; }' . "\n".
				'td { vertical-align: top; }' . "\n".
				'</style>' . "\n".
				'<!--[if gte mso 9]><xml>' . "\n".
				'<x:ExcelWorkbook>' . "\n".
				'<x:ExcelWorksheets>' . "\n".
				'<x:ExcelWorksheet>' . "\n".
				'<x:Name>general_report</x:Name>' . "\n".
				'<x:WorksheetOptions>' . "\n".
				'<x:Print>' . "\n".
				'<x:ValidPrinterInfo/>' . "\n".
				'</x:Print>' . "\n".
				'</x:WorksheetOptions>' . "\n".
				'</x:ExcelWorksheet>' . "\n".
				'</x:ExcelWorksheets>' . "\n".
				'</x:ExcelWorkbook>' . "\n".
				'</xml><![endif]-->' . "\n".
				'</head>' . "\n".
				'<body>' . "\n" );
        printf("<table>\n");

        // -- headers
        printf("<tr>\n");
        foreach($headers as $header) { printf("<th>".$header."</th>\n"); }
        printf("</tr>\n");

		// -- data
        for($i=0; $i<count($data); $i++) {
        	printf("<tr>\n");
        	foreach($data[$i] as $key => $d) {
        		if (strlen($d)<1) { $d = "-"; }
        		$d = str_replace(array("\r","\n"), array("", " "), $d);
        		printf("<td>".$d."</td>\n");
        	}
        	printf("</tr>\n");
        }

		// -- tail
		printf("</table>\n".
			   "</body>\n".
			   "</html>\n");

    	exit;
	}



	/**
	 * Generate table in text-csv format
	 *
	 * @param  array	$headers
	 * @param  array of arrays	$data
	 * @param  string	$filename, if defined filename, then csv download else return data
	 * @return void
	 */
	public function csvReport( $headers, $data, $filename=null ) {
		if ($filename) {
			header('Content-Description: File Transfer');
			header("Content-Type: text/csv");
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			header('Content-Transfer-Encoding: binary');
			header("Accept-Ranges: bytes");
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			ob_clean();
			flush();
		}

		$out = fopen('php://output', 'w');
		fputcsv($out, $headers, ';', '"');
		foreach($data as $i => $d) {
			fputcsv($out, $d, ';', '"');
		}
		fclose($out);
		exit;
	}


	/**
	 * Raise MantellaException
	 *
	 * @param  integer $cause
	 * @param  string $message
	 * @throws MantellaException
	 */
	public function error( $cause, $message=null ) {
        throw new MantellaException($message , (int)$cause);
	}


}