<?php
/**
 * MantellaMVC - A PHP Framework For Web Applications
 *
 * @class    MantellaModel
 * @version    1.4
 * @author    Vasilij Olhov <vsl@inbox.lv>
 */
abstract class MantellaModel {

	/**
	 * Set of fields.
	 *
	 * @var array
	 */
	protected $FIELDS = array();

	/**
	 * Name of ID-field (primary key)
	 *
	 * @var string
	 */
	protected $PRIMARY_KEY = null;

	/**
	 * Set of relations with other models
	 *
	 * @var array
	 */
    protected $RELATED = array();

    /**
     * Set of lazy load fields
     *
     * @var array
     */
    protected $LAZY_LOAD = array();

	/**
	 * Set of validation rules for model
	 *
	 * @var array
	 */
    protected $VALIDATORS = array();

	/**
	 * Set of custom validation error messages or pointer to section in locale file
	 *
	 * @var array|string
	 */
    protected $VALIDATORS_ERRORS = array();

	/**
	 * Name of database connection (defined in configuration). Assigned automatically by model settings.
	 *
	 * @var string
	 */
	protected $DATABASE = null;

	/**
	 * Name of table in database. Assigned automatically by model settings.
	 *
	 * @var string
	 */
	protected $TABLE	= null;

	/**
	 * Set of field values in model
	 *
	 * @var array
	 */
	private $_VALUES = array();

	/**
	 * Instance of database connection (MantellaDB). Assigned automatically by model settings.
	 *
	 * @var MantellaDB
	 */
	private $_DBLINK = null;

	/**
	 * Last error message.
	 *
	 * @var string
	 */
	private $_ERROR	 = null;

	/**
	 * Debug flag. If TRUE when output SQL queries and terminate appication.
	 *
	 * @var boolean
	 */
	protected $_SQL_DEBUG = false;

    /**
     * Lazy load flag. If TRUE when not select fields, marked in LAZY_LOAD list.
     *
     * @var boolean
     */
    protected $_LAZY_LOAD = false;

	/**
	 * Create a new model instance.
	 *
	 * @param  void
	 * @return void
	 */
	public function __construct( ) {
    	if ( $this->DATABASE) { $this->_DBLINK = DBM::get($this->DATABASE); }

        if ( is_string($this->VALIDATORS_ERRORS) ) { $this->VALIDATORS_ERRORS = LNG::get($this->VALIDATORS_ERRORS); }
	}



	/**
	 * Set SQL debug mode: output sql query
	 *
	 * @param  void
	 * @return MantellaModel
	 */
	public function debug() {
		$this->_SQL_DEBUG = !$this->_SQL_DEBUG;
		return $this;
	}

    /**
     * Set lazy load mode: output sql query
     *
     * @param  boolean	$flag
     * @return MantellaModel
     */
    public function lazy($flag=true) {
        $this->_LAZY_LOAD = $flag;
        return $this;
    }


	/**
	 * Model setter
	 *
	 * @param  string	$key
	 * @param  mixed	$value
	 * @param  boolean	$applyCustomModelChanges
	 * @return MantellaModel
	 */
    public function set($key, $value, $applyCustomModelChanges = true) {

        if ( isset($this->FIELDS[$key]) ) {
            $type = strtolower($this->FIELDS[$key]);

            switch( $type[0] ) {
                case 'num' : $this->_VALUES[$key] = floatval($value);
							 break;
				case 'int' : $this->_VALUES[$key] = intval($value);
							 break;
				case 'str' : $this->_VALUES[$key] = strval($value);
							 break;
				case 'bool': $this->_VALUES[$key] = (boolean)$value;
							 break;
				case 'date': $this->_VALUES[$key] = $value;
							 break;
		   		default: $this->_VALUES[$key] = $value;
			}

			$methodName = "set".ucfirst($key);
			if ($applyCustomModelChanges && method_exists($this, $methodName)) {
				$this->_VALUES[$key] = $this->$methodName( $this->_VALUES[$key] );
			}
		}

		return $this;
	}



	/**
	 * Model getter
	 *
	 * @param  string	$key
	 * @param  boolean	$applyCustomModelChanges
	 * @return mixed
	 */
	public function get($key, $applyCustomModelChanges = true) {
		$val = isset($this->_VALUES[$key]) ? $this->_VALUES[$key] : null;

        $methodName = "get".ucfirst($key);
        if ($applyCustomModelChanges && method_exists($this, $methodName)) {
			return $this->$methodName( $val );
		}

        return $val;
	}


	/**
	 * Multi-fields model setter
	 *
	 * @param  array	$values
	 * @return MantellaModel
	 */
	public function fill( $values ) {
		foreach($values as $key => $value) {
			$this->set($key, $value);
		}
		return $this;
	}


	/**
	 * Remove all field values
	 *
	 * @param  boolean	$exceptPrimaryKey
	 * @return MantellaModel
	 */
	public function clear( $exceptPrimaryKey = false ) {
		$pk = $this->getPrimaryKeyValue();
		$this->_VALUES = array();
		if ($exceptPrimaryKey) {
			$this->setPrimaryKey($pk);
		}
		return $this;
	}


	/**
	 *  Multi-fields model getter
	 *
	 * @param  boolean	$applyCustomModelChanges
	 * @return array
	 */
	public function row( $applyCustomModelChanges = true) {
		$result = $this->FIELDS;
        foreach($result as $key => $value) {
			$result[$key] = $this->get($key, $applyCustomModelChanges);
		}
		return $result;

	}



	/**
	 *  Slashed multi-fields model getter
	 *
	 * @param  boolean	$applyCustomModelChanges
	 * @return array
	 */
	public function srow( $applyCustomModelChanges = true) {
		$result = $this->row( $applyCustomModelChanges );
		foreach($this->FIELDS as $key => $type) {
			if ( ($type=="str" || $type='fake')  && array_key_exists($key,$result) ) {
				$result[$key] = htmlspecialchars($result[$key], ENT_QUOTES );
			}
		}
		return $result;
	}

	/**
	 *  Convert human-format date DD.MM.YYYY ot iso-format YYYY-MM-DD hh:nn:ss
	 *
	 * @param  string	$date
	 * @param  string	$time
	 * @return string
	 */
	public function date2Iso( $date, $time='00:00:00' ) {
		return substr($date, 6, 4) . "-" . substr($date, 3, 2) . "-" . substr($date, 0, 2) . " " . $time;
	}

	/**
	 *  Convert iso-format date YYYY-MM-DD hh:nn:ss to human date DD.MM.YYYY
	 *
	 * @param  string	$date
	 * @return string
	 */
	public function iso2Date( $date ) {
		return substr($date, 8, 2) . "." . substr($date, 5, 2) . "." . substr($date, 0, 4);
	}

	/**
	 *  Convert iso-format date YYYY-MM-DD hh:nn:ss to human date DD.MM.YYYY hh:nn:ss
	 *
	 * @param  string	$date
	 * @return string
	 */
	public function iso2Datetime( $date ) {
		return substr($date, 8, 2) . "." . substr($date, 5, 2) . "." . substr($date, 0, 4)." ".substr($date, 11, 5);
	}

	/**
	 *  Return random string from letters and digits
	 *
	 * @param  int	$length
	 * @return string
	 */
	public function genHash( $length=8 ) {
		$chars = array(
			'A','B','C','D','E','F','G','H','J','K','L','M','N','P','R','S','T','U','V','X','Y','Z',
			'2','3','4','5','6','7','8','9',
			'7','8','4','9','6','2','3','5',
		);
		$hash = "";
		for($i = 0; $i < $length; $i++) {
			$index = rand(0, count($chars) - 1);
			$hash .= $chars[$index];
		}
		return $hash;
	}



	/**
	 *  Return name of primary key field
	 *
	 * @param  void
	 * @return string
	 */
	public function getPrimaryKeyName() {
		return $this->PRIMARY_KEY;
	}


	/**
	 *  Return value of primary key field
	 *
	 * @param  void
	 * @return mixed
	 */
	public function getPrimaryKeyValue() {
		return $this->get( $this->getPrimaryKeyName() , false );
	}


	/**
	 *  Setter for primary key field
	 *
	 * @param  mixed	$value
	 * @return MantellaModel
	 */
	public function setPrimaryKey( $value ) {
		$this->set( $this->getPrimaryKeyName() , $value );
		return $this;
	}


	/**
	 *  Return field definitions
	 *
	 * @param  void
	 * @return array
	 */
	public function getFields() {
		return $this->FIELDS;
	}


	/**
	 *  Checks if such field defined in model
	 *
	 * @param  string	$fieldname
	 * @return boolean
	 */
	public function isFieldExists( $fieldname ) {
		return ( isset($this->FIELDS[$fieldname]) );
	}


	/**
	 *  Return type of field by name
	 *
	 * @param  string	$fieldname
	 * @return string
	 */
	public function getFieldType( $fieldname ) {
		return ( isset($this->FIELDS[$fieldname]) ? $this->FIELDS[$fieldname] : false );
	}


	/**
	 *  Checks model on vlidation rules
	 *
	 * @param  void
	 * @return boolean
	 */
	public function validate() {
		if ( count($this->VALIDATORS) == 0) {
			return true;
		}
		return MantellaValidator::check( $this->row(false) , $this->VALIDATORS, $this->VALIDATORS_ERRORS );
	}


	/**
	 *  Return errors after validation
	 *
	 * @param  void
	 * @return array
	 */
	public function getUnvalidated() {
    	return MantellaValidator::getUnvalidated();
	}


	/**
	 * Get table name, which in model defined
	 *
	 * @param  void
	 * @return string
	 */
    public function getTableName() {
    	return $this->TABLE;
    }


	/**
	 * Get database connection name (by configuration), which in model defined
	 *
	 * @param  void
	 * @return string
	 */
    public function getDatabaseName() {
		return $this->DATABASE;
    }


	/**
	 * Get database connection, which in model defined
	 *
	 * @param  void
	 * @return MantellaDB
	 */
    public function getDatabase() {
     	return $this->_DBLINK;
    }



	/**
	 * Return last error and clear errro in class
	 *
	 * @param  void
	 * @return string
	 */
	public function getLastError() {
		$error = $this->_ERROR;
		$this->_ERROR = null;
		return $error;
	}


	/**
	 * Remember last error in class
	 *
	 * @param  string	$message
	 * @return boolean
	 */
	private function _setError( $msg = null ) {
		$this->_ERROR = $msg;
		return is_null($msg);
	}


	/**
	 * Fetch record from database into model
	 *
	 * @param  string|integer	$id
	 * @return boolean
	 */
    public function fetch($id = null) {
    	if ( !$this->_DBLINK || !$this->_DBLINK->isConnected() ) {
    		return $this->_setError('not connected');
    	}
    	$id = ($id) ? $id : $this->getPrimaryKeyValue();
    	return ( ($id) ? $this->_selectModelFromDatabase($id) : $this->_setError('primary key not defined') );
    }


	/**
	 * Insert model into database
	 *
	 * @param  boolean	$reFillModel
	 * @return boolean
	 */
    public function add( $reFillModel = true) {
		if (!$this->_DBLINK || !$this->_DBLINK->isConnected() ) {
			return $this->_setError('not connected');
		};
		$fields = $this->_detectFields();
		return $this->_addModelToDatabase( $fields, $reFillModel );
	}



	/**
	 * Insert model into database if primary key is empty, or update if primary key exists
	 *
	 * @param  string	$rcondition
	 * @param  boolean	$reFillModel
	 * @return boolean
	 */
	public function save( $condition=null, $reFillModel = true ) {
		if (!$this->_DBLINK || !$this->_DBLINK->isConnected() ) {
			return $this->_setError('not connected');
		};
		$fields = $this->_detectFields();
		$pKey = $this->getPrimaryKeyValue();
		return ( ($pKey) ? $this->_updateModelInDatabase( $fields, $condition, $reFillModel ) : $this->_addModelToDatabase( $fields, $reFillModel ) );
	}


	/**
	 * Delete current model from database
	 *
	 * @param  string	$rcondition
	 * @return boolean
	 */
	public function remove( $condition=null ) {
		if ( !$this->_DBLINK || !$this->_DBLINK->isConnected() ) {
			return $this->_setError('not connected');
		};
		return $this->_deleteModelFromDatabase( $condition );
	}



	/**
	 * Generate SQL SELECT query
	 *
	 * @param  mixed	$id
	 * @param  string	$condition
	 * @param  string	$order
	 * @param  integer	$limit
	 * @return string
	 */
	public function _getSelectQuery_( $id, $condition=null, $order=null, $limit=null) {
		// handle related models
		$joinFields = $joinTables = null;
        $joinUsedTables = array();
        foreach($this->RELATED as $field => $params) {
            if (array_key_exists($params['bind'],$this->RELATED)) {
                $thisModelName = $this->RELATED[$params['bind']]['model']."Model";
                $thisModel = new $thisModelName;
                $thisTableName = $thisModel->getTableName();
                $thisbind = $this->RELATED[$params['bind']]['value'];
            }
            else {
                $thisTableName = $this->getTableName();
                $thisbind = $params['bind'];
            }
            $bindModelName = $params['model']."Model";
            $bindModel = new $bindModelName;
            $bindModelTable = $bindModel->getTableName();
            $joinFields .= ", ".$bindModelTable.".".$params['value']." as ".$field;
            if ( array_search($bindModelTable,$joinUsedTables) === false ) {
            	$joinUsedTables[] = $bindModelTable;
	            $joinTables .= "left join ".$bindModelTable." on (".$thisTableName.".".$thisbind." = ".$bindModelTable.".".$params['match'].") ";
            }
        }

		$sql = null;
        foreach( $this->FIELDS as $field => $desc ) {
        	if ( $this->_LAZY_LOAD === true && in_array($field, $this->LAZY_LOAD) ) { continue; }
            if ( array_key_exists($field,$this->RELATED) === false && $desc != 'fake' ) {
        		$sql .= (($sql) ? ", " : "") . $this->getTableName() . ".`" . $field . "` as `" . $field . "`";
        	}
        }
        $sql = "select " . $sql. " " . $joinFields . " from " . $this->getTableName(). " " . $joinTables . " ";

        $where = ($id) ? "where " . $this->getTableName().".".$this->getPrimaryKeyName() . "='" . $this->_DBLINK->safe($id) . "'" : null;
        if ($condition) { $where .=  ($where) ? " and " . str_replace("[table]", $this->getTableName(), $condition) : " where " . str_replace("[table]", $this->getTableName(), $condition); }
        $sql = $sql . $where;

        if ($order) {
        	$sql .= ( is_array($order) ) ? " order by ".$order['field']." ".$order['dir'] : " order by ".$order;
        }
        if ($limit) {
         	$sql .= ( is_array($limit) ) ? " limit ".$limit['offset'].", ".$limit['count'] : " limit ".$limit;
        }

        return $sql;
	}




	/**
	 * Function only for private use inside class
	 */
	private function _detectFields() {
		$fields = array();
		$data = $this->row(false);
		foreach($data as $name => $value) {
			if ( array_key_exists($name,$this->RELATED) || $value == null) { continue; }
			if ( $this->isFieldExists($name) && $this->getFieldType($name)=='fake') { continue; }

			$type = $this->getFieldType($name);
            if ( $type=="date" && $value=="NOW" ) { $value = "NOW()"; }
            else if ( $type=="date" && $value=="NULL" ) { $value = "null"; }
            else if ( $type=="str" && $value=="NULL" ) { $value = "null"; }
            else if ( $type=="str" || $type=="date" ) { $value = "'".$this->getDatabase()->safe($value)."'"; }
            else if ( $type=="bool" ) { $value = (($value==true) ? "1" : "0"); }
            else { $value = $value; };
            $fields[$name] = $value;
		}
		return $fields;
	}

	/**
	 * Function only for private use inside class
	 */
    private function _selectModelFromDatabase( $id ) {
        $this->clear();

        $sql = $this->_getSelectQuery_($id);
        if ($this->_SQL_DEBUG) { die($sql); }

        $res = $this->_DBLINK->execSQL($sql);

    	if ($res === false) {
    		$err = $this->_DBLINK->getLastError();
    		return $this->_setError( $err['text'] );
    	}

    	if ( is_array($res) && isset($res[0]) ) { $this->fill($res[0]); }
   		return $this->_setError(null);
	}

	/**
	 * Function only for private use inside class
	 */
    private function _addModelToDatabase( $fields, $reFillModel ) {
        $names = $values = null;
        foreach($fields as $name => $value) {
            $names  .= (($names) ? ", `" : " `").$name."`";
            $values .= (($values) ? ", " : " ").$value;
        }
        $sql = "insert into " . $this->TABLE . "(" . $names . ") values(" . $values . ")";

        if ($this->_SQL_DEBUG) { die($sql); }

        if ( $id = $this->getDatabase()->execSQL($sql) ) {
        	if ($reFillModel) { $this->_selectModelFromDatabase( $id ); }
        	return $this->_setError(null);
        }
		$error = $this->getDatabase()->getLastError();
		return $this->_setError( $error['text'] );
	}

	/**
	 * Function only for private use inside class
	 */
	private function _updateModelInDatabase( $fields, $condition=null, $reFillModel ) {
        $pKey = $this->getPrimaryKeyName();
        $sets = null;
        foreach($fields as $name => $value) {
       		if ($name == $pKey) { continue; } // cut primary key from update query
       		$sets .= (($sets) ? ", " : "") . "`".$name."`=".$value;
        }
        $sql = "update " . $this->TABLE . " set " . $sets . " where ".$pKey."='".$this->getDatabase()->safe( $this->getPrimaryKeyValue() )."'";
        $sql .= ($condition) ? " and ".str_replace("[table]", $this->getTableName(), $condition) : "";

        if ($this->_SQL_DEBUG) { die($sql); }

        if ( $this->getDatabase()->execSQL($sql) !== false ) {
        	if ($reFillModel) { $this->_selectModelFromDatabase( $this->getPrimaryKeyValue() ); }
        	return $this->_setError( null );
        }
		$error = $this->getDatabase()->getLastError();
		return $this->_setError( $error['text'] );
	}

	/**
	 * Function only for private use inside class
	 */
    private function _deleteModelFromDatabase( $condition=null ) {
    	$sql = "delete from ". $this->TABLE . " where " . $this->getPrimaryKeyName() . "='" . $this->getDatabase()->safe( $this->getPrimaryKeyValue() ) . "'";
        $sql .= ($condition) ? " and ".str_replace("[table]", $this->getTableName(), $condition) : "";

        if ($this->_SQL_DEBUG) { die($sql); }

        if ( $this->getDatabase()->execSQL($sql) !== false ) {
            $this->clear();
            return $this->_setError( null );
        }
        $error = $this->getDatabase()->getLastError();
        return $this->_setError( $error['text'] );
    }

}