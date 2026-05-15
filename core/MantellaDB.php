<?php
// =============================================================================================
// ====================================== DB Core Class ========================================
// =============================================================================================
class MantellaDB {

	// List of available drivers
	private $DRIVERS = array(
		'MYSQL' 	=> array( 'checker'  => "mysqli_connect",
							  'className'=> "MantellaMySQL"
							),
		'POSTGRES' 	=> array( 'checker'  => "pg_connect",
							  'className'=> "MantellaPostGres"
							),
		'ORACLE'	=> array( 'checker'  => "oci_connect",
							  'className'=> "MantellaOracle"
							),
		'MSSQL'		=> array( 'checker'  => "mssql_connect",
							  'className'=> "MantellaMSSQL"
							),
        	'PDO'       => array( 'checker'  => null,
                	              'className'=> "MantellaPDO" 
                        	    ),
		);

	// Last error structure
    private $dbError = array(
    							'code' => 0,
    							'text' => ''
    );

    // Database connection parameters
    private $dbParams = array(
        'driver' 	=> null,
        'host' 	  	=> null,
        'port' 	  	=> 0,
        'database' 	=> null,
        'username' 	=> null,
        'password'	=> null,
        'charset' 	=> null,
        'prefix'	=> null,
        'class'		=> null,
        'logger'    => null,
    );


    public function __construct( $settings=null ) {
    	if ( is_array($settings) ) { $this->_init($settings); }
    }


	public function connect( $settings=null ) {
		if ( is_array($settings) ) {
			$this->_init($settings);
		}

        if ( !$this->_isDriverDefined())  {
        	return $this->_setResult(13,"Driver [".$this->dbParams['driver']."] not initialized");
        }

        if ( $this->_isConnected() )  {
        	return $this->_setResult(0,"Connected");
        }

		$res = $this->dbParams['class']->connect( $this->dbParams );

        return ($res === true) ? $this->_setResult( 0, 'Connected')
        					   : $this->_setResult( 14, $res);
	}


    public function safe( $value ) {
    	return ($this->dbParams['class']) ? $this->dbParams['class']->safe_value($value) : addslashes($value);
    }

    public function setLogger($callback) {
        $this->dbParams['logger'] = $callback;
    }

    public function getLink() {
        return $this->dbParams['class'] ? $this->dbParams['class']->getLink() : null;
    }


    public function execSQL( $query, $param=null ) {
    	$what_return = strtoupper( substr($query,0,strpos(trim($query),' ')) );
		switch( $what_return ) {
			case 'SELECT' : $what_return = "ROW"; break;
			case 'SHOW'   : $what_return = "ROW"; break;
			case 'INSERT' : $what_return = "ID";  break;
			case 'UPDATE' : $what_return = "NUM"; break;
			case 'REPLACE': $what_return = "NUM"; break;
			case 'DELETE' : $what_return = "NUM"; break;
		}

        $timer_started = microtime( true );
        $res = $this->dbParams['class']->sql($query, $what_return, $param);
        $timer_message = sprintf("Execution time: %.6f sec.\n", microtime( true ) - $timer_started );
        $timer_message .= "Query: [".$query."]\n";

        if ( $res === true) {
    		$this->_setResult(0, 'Query executed');
            $resp = $this->dbParams['class']->getResult();
            if ( is_callable($this->dbParams['logger']) ) {
                switch( $what_return ) {
                    case 'ROW' : $timer_message .= "Returns: ".count($resp)." rows"; break;
                    case 'NUM' : $timer_message .= "Returns: ".$resp." affected rows"; break;
                    case 'ID'  : $timer_message .= "Returns: row ID#".$resp;  break;
                }
                $this->dbParams['logger']($timer_message."\n");
            }
            return $resp;
    	}

        if ( is_callable($this->dbParams['logger']) ) {
            $timer_message .= "Error: ".$res;
            $this->dbParams['logger']($timer_message."\n");
        }
    	return $this->_setResult(15, $res);

	}


 	public function execProcedure( $procName, $params=array() ) {
 		if (!method_exists($this->dbParams['class'],"proc")) {
 			return $this->_setResult(17, 'Procedure execution for driver ['.$this->dbParams['driver'].'] not supported');
 		}

 		$res = $this->dbParams['class']->proc($procName, $params);
 		if ( $res === true) {
    		$this->_setResult(0, 'Procedure executed');
    	    return $this->dbParams['class']->getResult();
    	}
    	return $this->_setResult(16, $res);

 	}


 	public function execFunction( $funcName, $params=array() ) {

 		if (!method_exists($this->dbParams['class'],"func")) {
 			return $this->_setResult(17, 'Function execution for driver ['.$this->dbParams['driver'].'] not supported');
 		}

 		$res = $this->dbParams['class']->func($funcName, $params);
 		if ( $res === true) {
    		$this->_setResult(0, 'Function executed');
    	    return $this->dbParams['class']->getResult();
    	}
    	return $this->_setResult(16, $res);
 	}


    public function disconnect() {
    	if ( !$this->isConnected() ) { return $this->_setResult(16, "Not connected" ); }
    	$this->dbParams['class']->disconnect();
        return $this->_setResult(0, "Disconnected" );
    }


    public function getLastError() {
    	$err = $this->dbError;
	   	$this->dbError = array( 'code'=>0, 'text' => '' );
      	return $err;
    }

    public function isConnected() {
    	return $this->_isConnected();
    }


    private function _init( $settings ) {
        $this->dbParams = array(
            'driver'    => (isset($settings['driver']))     ? strtoupper(trim($settings['driver'])) : $this->dbParams['driver'],
            'host'      => (isset($settings['host']))       ? trim($settings['host']) 				: $this->dbParams['host'],
            'port'  	=> (isset($settings['port'])) 	    ? (int)$settings['port'] 				: $this->dbParams['port'],
            'database'  => (isset($settings['database'])) 	? trim($settings['database']) 			: $this->dbParams['database'],
            'username'	=> (isset($settings['username'])) 	? trim($settings['username']) 			: $this->dbParams['username'],
            'password'	=> (isset($settings['password'])) 	? trim($settings['password']) 			: $this->dbParams['password'],
            'charset' 	=> (isset($settings['charset'])) 	? trim($settings['charset']) 			: $this->dbParams['charset'],
            'prefix'	=> (isset($settings['prefix']))     ? trim($settings['prefix']) 			: $this->dbParams['prefix'],
			'class'		=> null,
            'logger'    => null,
    	);
        $cl = $this->_getDriverClass( $this->dbParams['driver'] );
        $this->dbParams['class'] = ($cl) ? $cl : null;
	}


    private function _getDriverClass($driver) {
        if ( !isset($this->DRIVERS[$driver]) ) { 
            return $this->_setResult(10, "Driver [".$driver."] not supported by class" ); 
        }
		
        if ( $this->DRIVERS[$driver]['checker'] && !function_exists($this->DRIVERS[$driver]['checker']) ) { 
            return $this->_setResult(11, "Driver [".$driver."] not supported by PHP" ); 
        }
        
        if ( !class_exists($this->DRIVERS[$driver]['className']) ) { 
            return $this->_setResult(12, "Class of driver [".$driver."] not found" ); 
        }

      	$cl = $this->DRIVERS[$driver]['className'];
        return new $cl;
    }


    private function _isDriverDefined() {
    	return is_object($this->dbParams['class']);
    }


    private function _isConnected() {
    	return ( $this->_isDriverDefined() && $this->dbParams['class']->getLink() );
    }


	private function _setResult( $code, $message) {
		$this->dbError = array(
								'code' => (int) $code,
								'text' => $message
							  );
        return ( (int)$code ==0 );
	}

}





// =============================================================================================
// ====================================== PDO Driver =========================================
// =============================================================================================
class MantellaPDO {

    private $dbLink = null;
    private $dbPrefix = null;
    private $dbResult = null;

	public function getLink() {
		return $this->dbLink;
	}

    public function getResult() {
    	return $this->dbResult;
    }

	public function connect( $params ) {
		$db = null;
        try {
            $h = explode("@",$params['host']); 
            $connString = $h[0] . ":host=" . $h[1] . ";" . 
                          ($params['port'] ? "port=".$params['port'].";" : "" ) .
                          "dbname=".$params['database'] . 
                          ($params['charset'] ? ";charset=".$params['charset'] : "" ); 
            
            $db = new PDO($connString, $params['username'], $params['password']);  
            $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION ); 
        }
        catch(PDOException $e) {
           return $e->getMessage();
        }

        $this->dbLink = $db;
        $this->dbPrefix = $params['prefix'];
        return true;
       
	}

    
    public function sql($query, $what_return, $param=null) {
        $this->dbResult = null;
        try {
            $stmt = $this->dbLink->prepare($query);
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute();      
        }
        catch(Exception $e) {
            return $e->getMessage();
        }
        
        switch ($what_return) {
        	case 'ROW': $this->dbResult = array();
                        $this->dbResult = $stmt->fetchAll();
        				break;
			case 'ID':	$this->dbResult = $this->dbLink->lastInsertId();
						break;
			case 'NUM': $this->dbResult = $stmt->rowCount();
					    break;
        }
        $stmt->closeCursor();
        
        return true;
    }

	public function disconnect() {
		$this->dbLink = null;
	}

    public function safe_value($value) {
    	return addslashes($value);
    }

}







// =============================================================================================
// ====================================== MySql Driver =========================================
// =============================================================================================
class MantellaMySQL {

    private $dbLink = null;
    private $dbPrefix = null;
    private $dbResult = null;

	public function getLink() {
		return $this->dbLink;
	}

    public function getResult() {
    	return $this->dbResult;
    }

	public function connect( $params ) {
		$db = null;
		$host = ( (int)$params['port']>0 ) ? $params['host'].":".$params['port'] : $params['host'];

		if ( !($db = @mysqli_connect($host,$params['username'],$params['password'],$params['database'])) ) {
			return @mysqli_connect_error();
		}

  		if ( !@mysqli_query($db, "SET NAMES '".$params['charset']."'" ) ) {
  			return mysqli_error($db);
  		}

    	$this->dbLink = $db;
    	$this->dbPrefix = $params['prefix'];
        return true;
	}

    public function sql($query, $what_return, $param=null) {
        $this->dbResult = null;

        if (!($res = @mysqli_query($this->dbLink , $query))) { return mysqli_error($this->dbLink); }

        switch ($what_return) {
        	case 'ROW': $this->dbResult = array();
                        while ($row = mysqli_fetch_assoc($res)) { $this->dbResult[] = $row; }
        				break;
			case 'ID':	$this->dbResult = mysqli_insert_id($this->dbLink);
						break;
			case 'NUM': $this->dbResult = mysqli_affected_rows($this->dbLink);
					    break;
        }

        @mysqli_free_result($res);
        return true;
    }

	public function disconnect() {
		mysqli_close($this->dbLink);
	}

    public function safe_value($value) {
    	return ($this->dbLink) ? mysqli_real_escape_string($this->dbLink, $value) : addslashes($value);
    }

}




// =============================================================================================
// ====================================== MSSQL Driver =========================================
// =============================================================================================
class MantellaMSSQL {

    private $dbLink = null;
    private $dbPrefix = null;
    private $dbResult = null;

    public function getLink() {
        return $this->dbLink;
    }

    public function getResult() {
        return $this->dbResult;
    }

    public function connect( $params ) {

        $db = null;
        $host = ( (int)$params['port']>0 ) ? $params['host'].":".$params['port'] : $params['host'];

        if ( !($db = @mssql_connect($host,$params['username'],$params['password'])) ) {
			return @mssql_get_last_message();
		}
		if (! (@mssql_select_db( trim($dbName),$db))) {
        	return @mssql_get_last_message();
		}

    	$this->dbLink = $db;
    	$this->dbPrefix = $params['prefix'];
        return true;
    }

    public function sql($query, $what_return, $param=null) {
        $this->dbResult = null;

        if ($what_return=='ID' && $param) { $query .= "; SELECT SCOPE_IDENTITY() AS IDENTITY_COLUMN_NAME"; }

        if (!($res = @mssql_query($this->dbLink , $query))) { return @mssql_get_last_message(); }

        switch ($what_return) {
            case 'ROW': $this->dbResult = array();
                        while ($row = @mssql_fetch_assoc($res)) { $this->dbResult[] = $row; }
                        break;
            case 'ID':  if ($param) {
                            $row = @mssql_fetch_array($res, MSSQL_NUM);
                            $this->dbResult = $row[0];
                        }
                        else { $this->dbResult = 0; }
                        break;
            case 'NUM': $this->dbResult = @mssql_rows_affected($res);
                        break;
        }

        @mssql_free_result($res);
        return true;
    }

    public function disconnect() {
        @mssql_close($this->dbLink);
    }

    public function safe_value($value) {
        return addslashes($value);
    }

}




// =============================================================================================
// ===================================== POSTGRES Driver =======================================
// =============================================================================================
class MantellaPostGres {

    private $dbLink = null;
    private $dbPrefix = null;
    private $dbResult = null;

    public function getLink() {
        return $this->dbLink;
    }

    public function getResult() {
        return $this->dbResult;
    }

    public function connect( $params ) {

        $db = null;
        $connection_string = "host=".$params['host']." port=" . ( ( (int)$params['port']>0 ) ? $params['port'] : "5432" )." ".
        					 "dbname=".$params['database']." user=".$params['username']." password=".$params['password'];

		if ( !($db = @pg_connect($connection_string)) ) {
			return @pg_last_error();
		}

        if ( $params['charset'] ) {
        	if (@pg_set_client_encoding($db,  $params['charset']) != 0) {
				return @pg_last_error();
            }
        }

        $this->dbLink = $db;
        $this->dbPrefix = $params['prefix'];
        return true;
    }

    public function sql($query, $what_return, $param=null) {
        $this->dbResult = null;

		if ($what_return=='ID' && $param) { $query .= " RETURNING ".$param; }

        if (!($res = @pg_query($this->dbLink , $query))) { return @pg_last_error($this->dbLink); }

        switch ($what_return) {
            case 'ROW': $this->dbResult = array();
                        while ($row = @pg_fetch_assoc($res)) { $this->dbResult[] = $row; }
                        break;
            case 'ID':  if ($param) {
            				$row = @pg_fetch_assoc($res);
                            $this->dbResult = $row[$param];
            			}
            			else { $this->dbResult = 0; }
                        break;
            case 'NUM': $this->dbResult = @pg_affected_rows($res);
                        break;
        }

        @pg_free_result($res);
        return true;
    }

	public function func( $functionName, $parameters) {
		$params_str = null;
		foreach($parameters as $parameter) {
			$params_str .= ($params_str) ? ", " : "";
			if ( is_null($parameter) ) { $params_str .= "NULL"; }
			elseif ( is_int($parameter) ) { $params_str .= $parameter; }
			else { $params_str .= "'".$this->safe_value($parameter)."'"; };
        }
		$query = "select ".$functionName."(" . $params_str . ")";
		return $this->sql($query, "ROW");
	}

	public function proc( $procedureName, $parameters ) {
		return $this->func( $procedureName, $parameters);
	}

    public function disconnect() {
        @pg_close($this->dbLink);
    }

    public function safe_value($value) {
    	return ($this->dbLink) ? pg_escape_string($this->dbLink, $value) : addslashes($value);
    }

}





// =============================================================================================
// ====================================== Oracle Driver ========================================
// =============================================================================================
class MantellaOracle {

    private $dbLink = null;
    private $dbPrefix = null;
    private $dbResult = null;

    public function getLink() {
        return $this->dbLink;
    }

    public function getResult() {
        return $this->dbResult;
    }

    public function connect( $params ) {
        $db = null;

        if ( !($db = @oci_connect( $params['username'], $params['password'], $params['host'], ($params['charset'])?$params['charset']:null )) ) {
        	return @oci_error();
		}

        $this->dbLink = $db;
        $this->dbPrefix = $params['prefix'];
        return true;
    }

    public function sql($query, $what_return, $param=null) {
        $this->dbResult = null;

        if ($what_return=='ID' && $param) { $query .= " returning ".$param; }

        if (!($res = @oci_parse($this->dbLink , $query))) {
        	$e = @oci_error($res);
        	return $e['message'];
        }
        if ( !@oci_execute($res, OCI_COMMIT_ON_SUCCESS) ) {
        	$e = @oci_error($res);
            return $e['message'];
        }

        switch ($what_return) {
            case 'ROW': $this->dbResult = array();
                        while ($row = @oci_fetch_assoc($res)) { $this->dbResult[] = $row; }
                        break;
            case 'ID':  if ($param) {
            				$row = @oci_fetch_array($res,OCI_NUM);
            				$this->dbResult = $row[0];
            			}
            			else { $this->dbResult = 0; }
                        break;
            case 'NUM': $this->dbResult = @oci_num_rows($res);
                        break;
        }

        @oci_free_statement($res);
        return true;
    }

    public function disconnect() {
        @oci_close($this->dbLink);
    }

    public function safe_value($value) {
        return addslashes($value);
    }

}
