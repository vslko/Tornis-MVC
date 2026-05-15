<?php
/**
 * MantellaMVC - A PHP Framework For Web Applications
 *
 * @class	MantellaCollection
 * @version	1.4
 * @author	Vasilij Olhov <vsl@inbox.lv>
 */
abstract class MantellaCollection {

	/**
	 * Name of assigned model (MantellaModel) in the collection.
	 *
	 * @var string
	 */
    protected $MODEL = null;

	/**
	 * Name of database connection (defined in cofiguration). Assigned automatically by model settings.
	 *
	 * @var string
	 */
    protected $DATABASE = null;

	/**
	 * Name of table in database. Assigned automatically by model settings.
	 *
	 * @var string
	 */
    protected $TABLE = null;

	/**
	 * Empty object of model, of instances which consists a collection
	 *
	 * @var MantellaModel
	 */
    private $_model_instance = null;

	/**
	 * Instance of database connection (MantellaDB). Assigned automatically by model settings.
	 *
	 * @var MantellaDB
	 */
   	private $_db = null;

	/**
	 * Array of model's instances.
	 *
	 * @var array
	 */
    private $_data = array();

	/**
	 * Pointer to the current element of collection.
	 *
	 * @var integer
	 */
    private $_pointer = 0;

	/**
	 * Debug flag. If TRUE when output SQL queries and terminate appication.
	 *
	 * @var boolean
	 */
    protected $_SQL_DEBUG = false;	// debug mode for sql's, output sql and terminate script

    /**
     * Lazy load flag. If TRUE when not select fields, marked in LAZY_LOAD list.
     *
     * @var boolean
     */
    protected $_LAZY_LOAD = false;

	/**
	 * Create a new collection instance.
	 *
	 * @param  void
	 * @return void
	 */
    public function __construct() {
    	// *** do nothing ***
    }



	/**
	 * Set SQL debug mode: output sql query
	 *
	 * @param  void
	 * @return MantellaCollection
	 */
	public function debug() {
		$this->_SQL_DEBUG = !$this->_SQL_DEBUG;
		return $this;
	}


    /**
     * Set lazy load mode: output sql query
     *
     * @param  boolean	$flag
     * @return MantellaCollection
     */
    public function lazy($flag=true) {
        $this->_LAZY_LOAD = $flag;
        return $this;
    }


	/**
	 * Return current element and sets pointer to the next element of collection.
	 *
	 * @param  void
	 * @return MantellaModel
	 */
	public function next() {
		$res = $this->getByIndex($this->_pointer);
		$this->_pointer++;
		return $res;	}


	/**
	 * Reset pointer to the begin of collection
	 *
	 * @param  void
	 * @return MantellaCollection
	 */
	public function reset() {
		$this->_pointer = 0;
		return $this;	}


	/**
	 * Reset pointer to the begin of collection and return first element
	 *
	 * @param  void
	 * @return MantellaModel
	 */
	public function first() {
		$this->reset();
		return $this->next();
	}


	/**
	 * Return thelast element of collection
	 *
	 * @param  void
	 * @return MantellaModel
	 */
    public function last() {
		return $this->getByIndex( $this->length()-1 );
    }


	/**
	 * Get all elements (MantellaModel) as array
	 *
	 * @param  void
	 * @return array
	 */
    public function getAll() {
        return $this->_data;
    }


	/**
	 * Get all models as array of arrays
	 *
	 * @param boolean	$applyCustomModelChanges
	 * @return array
	 */
	public function rows( $applyCustomModelChanges=true ) {
		$result = array();
		$this->reset();
		while($model = $this->next()) {
			$result[] = $model->row($applyCustomModelChanges);
		}
		return $result;
	}


	/**
	 *  Get all slashed multi-fields models as array of arrays
	 *
	 * @param  boolean	$applyCustomModelChanges
	 * @return array
	 */
	public function srows( $applyCustomModelChanges = true) {
		$result = array();
		$this->reset();
		while($model = $this->next()) {
			$result[] = $model->srow($applyCustomModelChanges);
		}
		return $result;
	}


	/**
	 * Get count of elements in collection
	 *
	 * @param  void
	 * @return integer
	 */
	public function length() {
		return count($this->_data);
	}


	/**
	 * Get element of collection by index, or FALSE if element not found
	 *
	 * @param  integer	$index
	 * @return MantellaModel|boolean
	 */
	public function getByIndex( $index ) {
		return ( ($index < count($this->_data))	? $this->_data[$index] : false );
	}


	/**
	 * Get ID (primary key) value of element of collection by index, or FALSE if element not found
	 *
	 * @param  integer	$index
	 * @return string
	 */
	public function getIdByIndex( $index ) {
		$pk = $this->getModel()->getPrimaryKeyName();
		if ($pk && ($index < count($this->_data)) )  {
			return $this->_data[$index]->get($pk);
		}
		return false;
	}


	/**
	 * Get index of element by ID (primary key) od model, or FALSE if element not found
	 *
	 * @param  string	$model_id
	 * @return integer
	 */
	public function getIndexById( $id ) {
    	for ($i=0; $i<$this->length(); $i++) {
    		if ( $this->getIdByIndex($i) == $id ) { return $i; }
    	}
		return false;
	}


	/**
	 * Get element by ID (primary key), or FALSE if element not found
	 *
	 * @param  string	$model_id
	 * @return MantellaModel
	 */
	public function getById( $id ) {
    	for ($i=0; $i<$this->length(); $i++) {
    		if ( $this->getIdByIndex($i) == $id ) { return $this->getByIndex($i); }
    	}
		return false;
	}


	/**
	 * Get value from model by $field_name, founded in collection by index, or FALSE if element not found
	 *
	 * @param  integer	$index
	 * @param  string	$field_name
	 * @return mixed
	 */
	public function getValueByIndex( $index , $field ) {
		return ( ($index < count($this->_data)) ? $this->_data[$index]->get($field) : null );
	}

	/**
	 * Get value from model by $field_name, founded in collection by model ID (primary key), or FALSE if element not found
	 *
	 * @param  string	$model_id
	 * @param  string	$field_name
	 * @return mixed
	 */
	public function getValueById( $id , $field ) {
		if ( $model = $this->getById($id) ) {
			return ( $model ? $model->get($field) : null );
		}
		return null;
	}

    /**
     * Get first model where fields equals value, or FALSE if model not found
     *
     * @param  string	$field
     * @param  mixed	$value
     * @return mixed
     */
	public function getByField($field, $value) {
	    $this->reset();
        while( $m = $this->next() ) {
            if ($m->get($field) == $value) {
                return $m;
            }
        }
        return false;
    }

	/**
	 * Get array of value for concrete field for all models
	 *
	 * @param  string	$field
	 * @param  boolean	$unique
	 * @return array
	 */
	public function getArrayOfField( $field, $unique=false ) {
		$result = array();
		$this->reset();
		while( $m = $this->next() ) {
			$result[] = $m->get($field);
		}
		return $unique ? array_unique($result) : $result;
	}



	/**
	 * Sort elements in collection by field of moddel in ASC/DESC order.
	 *
	 * @param  string	$name
	 * @param  string	$method
	 * @return MantellaCollection
	 */
	public function sort( $name="id", $method="asc" ) {
        if (!($this->getModel()->isFieldExists($name)) || $this->length()==0 ) { return $this; }
        $t = ( $this->getModel()->getFieldType($name) == "str" ) ? SORT_STRING : SORT_NUMERIC;
        $m = ( strtoupper($method) == "ASC" ) ? SORT_ASC : SORT_DESC;

		$data = array();
    	for ($i=0; $i<$this->length(); $i++) {
    		$data[$i] = $this->_data[$i]->row();
    		$data[$i]['___mantella_model___'] = $this->_data[$i];
    	}

    	foreach($data as $c=>$key) { $sorter[] = $key[$name]; }
    	array_multisort($sorter, $m, $t, $data);

        $this->clear();
        for($i=0; $i<count($data); $i++) {
        	$this->add( $data[$i]['___mantella_model___'] );
        }

		return $this;
	}



	/**
	 * Add model into tail of collection.
	 *
	 * @param  MantellaModel	$model
	 * @return MantellaCollection
	 */
	public function add( $model ) {
		if ( get_class($model) == get_class($this->getModel()) ) {
			$this->_data[] = $model;
		}
		return $this;
	}


	/**
	 * Replace model in colletion with new model by ID (primary key) of new model) or by index in collection.
	 *
	 * @param  MantellaModel	$model
	 * @param  integer	$index
	 * @return MantellaCollection
	 */
    public function replace( $model, $index=null ) {
    	if (!$index) { $index = $this->getIndexById( $model->getPrimaryKeyValue() ); }
    	if ($index) {
    		$this->_data[$index] = $model;
    	}
    	return $this;
    }


	/**
	 * Remove model from collection by ID (primary key) of model.
	 *
	 * @param  MantellaModel	$model
	 * @return MantellaCollection
	 */
    public function remove( $model ) {
    	$index = ( is_object($model) && get_class($model)==get_class($this->getModel()) ) ?  $this->getIndexById( $model->getPrimaryKeyValue() ) : $model;
    	if ( isset($this->_data[$index]) ) {
    		$this->_data = array_merge(
    									array_slice($this->_data,0,$index) ,
    									array_slice($this->_data, $index+1)
    		);
    	}
    	return $this;
    }


	/**
	 * Remove all elements from collection.
	 *
	 * @param  void
	 * @return MantellaCollection
	 */
    public function clear() {
    	$this->_data = array();
  		$this->reset();
  		return $this;
    }



	/**
	 * Get sum for field of records in database founded by sql-condition
	 *
	 * @param  string	$field_name
	 * @param  string	$condition
	 * @return float
	 */
	public function dbSumm( $field, $condition=null ) {
		$sql = $this->getModel()->_getSelectQuery_(null, $condition);
		$sql = preg_replace( "'select .* from 'si",
					 		 "select sum(".$this->getTableName().".".$field.") as total from ",
					 		 $sql );
        if ($this->_SQL_DEBUG) { die($sql); }
        $res = $this->_db->execSql( $sql );
        return ( is_array($res) ) ? $res[0]['total'] : 0;
	}


	/**
	 * Get count of records in database founded by sql-condition
	 *
	 * @param  string	$condition
	 * @return integer
	 */
	public function dbCount( $condition=null ) {
		$sql = $this->getModel()->_getSelectQuery_(null, $condition);
		$sql = preg_replace( "'select .* from 'si",
					 		 "select count(".$this->getTableName().".".$this->getModel()->getPrimaryKeyName().") as total from ",
					 		 $sql );
        if ($this->_SQL_DEBUG) { die($sql); }
        $res = $this->_db->execSql( $sql );
        return ( is_array($res) ) ? $res[0]['total'] : 0;
	}



	/**
	 * Get max value for field of records in database founded by sql-condition
	 *
	 * @param  string	$field_name
	 * @param  string	$condition
	 * @return float
	 */
	public function dbMax($field, $condition=null ) {
		$sql = $this->getModel()->_getSelectQuery_(null, $condition);
		$sql = preg_replace( "'select .* from 'si",
					 		 "select max(".$this->getTableName().".".$field.") as maximum from ",
					 		 $sql );
        if ($this->_SQL_DEBUG) { die($sql); }
        $res = $this->_db->execSql( $sql );
        return ( is_array($res) ) ? $res[0]['maximum'] : null;
	}


	/**
	 * Get min value for field of records in database founded by sql-condition
	 *
	 * @param  string	$field_name
	 * @param  string	$condition
	 * @return float
	 */
	public function dbMin($field, $condition=null ) {
		$sql = $this->getModel()->_getSelectQuery_(null, $condition);
		$sql = preg_replace( "'select .* from 'si",
					 		 "select min(".$this->getTableName().".".$field.") as minimum from ",
					 		 $sql );
        if ($this->_SQL_DEBUG) { die($sql); }
        $res = $this->_db->execSql( $sql );
        return ( is_array($res) ) ? $res[0]['minimum'] : null;
	}


	/**
	 * Get average value for field of records in database founded by sql-condition
	 *
	 * @param  string	$field_name
	 * @param  string	$condition
	 * @return float
	 */
	public function dbAverage($field, $condition=null ) {
		$sql = $this->getModel()->_getSelectQuery_(null, $condition);
		$sql = preg_replace( "'select .* from 'si",
					 		 "select avg(".$this->getTableName().".".$field.") as average from ",
					 		 $sql );
        if ($this->_SQL_DEBUG) { die($sql); }
        $res = $this->_db->execSql( $sql );
        return ( is_array($res) ) ? $res[0]['average'] : null;
	}


	/**
	 * Fill collecton with models from database founded by $condition, ordered by $order, limited by $limit count
	 *
	 * @param  string	$condition
	 * @param  string	$order
	 * @param  integer	$limit
	 * @return MantellaCollection
	 */
	public function dbLoad( $condition=null, $order=null, $limit=null ) {
    	$this->clear();

        $sql = $this->getModel()->lazy($this->_LAZY_LOAD)->_getSelectQuery_(null, $condition, $order, $limit);

        if ($this->_SQL_DEBUG) { die($sql); }

        $res = $this->_db->execSQL( $sql );
    	if (is_array($res)) { // if records exists
    		foreach($res as $index => $record) {
    			$new_model = clone $this->getModel();
    			$new_model->fill($record);
    			$this->add( $new_model );
    		}
    	}
    	return $this;
	}


	/**
	 * Update all models of collection in database
	 *
	 * @param  void
	 * @return MantellaCollection
	 */
	public function dbUpdate() {
		if ( !($db = $this->getDB())  || !$this->length() ) { return false; }

        $curr_pointer = $this->_pointer;

        $this->reset();
        $ids = array();
        while( $model = $this->next() ) {        	$model->save(false);        	$ids[] = "'".$db->safe( $model->getPrimaryKeyValue() )."'";
        }
        $this->dbLoad( $this->getModel()->getPrimaryKeyName()." in (".implode(",",$ids).")" );

        $this->_pointer = $curr_pointer;

		return $this;
	}


	/**
	 * Delete records from database by sql-condition
	 *
	 * @param  string #condition
	 * @return boolean
	 */
    public function dbRemove( $condition=null ) {
    	if ( !($db = $this->getDB()) ) { return false; }

    	$sql = "DELETE FROM ".$this->TABLE . ( $condition ? " WHERE ".$condition : "" );
    	if ($this->_SQL_DEBUG) { die($sql); }

    	return (boolean)$db->execSQL($sql);
    }


	/**
	 * Delete all elements of collection from collection and database too
	 *
	 * @param  void
	 * @return boolean
	 */
    public function dbClear( ) {
    	if ( !($db = $this->getDB()) || !$this->length() ) { return false; }

		$ids = array();

		$this->reset();
		while( $model = $this->next() ) {			$ids[] = "'".$db->safe($model->getPrimaryKeyValue())."'";		}
    	$sql = "DELETE FROM ".$this->TABLE." ".
    		   "WHERE ".$this->_model_instance->getPrimaryKeyName()." in (".implode(",",$ids).")";

        if ($this->_SQL_DEBUG) { die($sql); }

    	if ($db->execSQL($sql)) {
    		 $this->clear();
    		 return true;
    	}
    	return false;
    }


	/**
	 * Define model as element for the collection
	 *
	 * @param  MantellaModel $model
	 * @return void
	 */
	public function setModelInstance( $model ) {
		if (!$this->_model_instance) {
			$this->_model_instance = $model;
			if (!$this->TABLE) {
				$this->TABLE = $model->getTableName();
			}
			if (!$this->DATABASE) {
				$this->DATABASE  = $model->getDatabaseName();
				$this->_db = $model->getDatabase();
			}
			else { $this->_db = $this->getDB(); }
		}
	}


	/**
	 * Get name of element (model)
	 *
	 * @param  void
	 * @return string
	 */
	public function getModelName() {
		return $this->MODEL;
	}


	/**
	 * Get empty model instance, assigned to collection
	 *
	 * @param  void
	 * @return MantellaModel
	 */
	public function getModel() {
		return $this->_model_instance;
	}


	/**
	 * Get table name, which in models defined
	 *
	 * @param  void
	 * @return string
	 */
	public function getTableName() {
		return $this->TABLE;
	}


	/**
	 * Get database connection name (by configuration), which in models defined
	 *
	 * @param  void
	 * @return string
	 */
	public function getDatabaseName() {
		return $this->DATABASE;
	}


	/**
	 * Get database connection, which in models defined, or FALSE if database connection not assigned/connected
	 *
	 * @param  void
	 * @return MantellaDB|boolean
	 */
	public function getDatabase() {
		return $this->getDB();
	}


	/**
	 * Return database connection, or FALSE if database connection not assigned/connected
	 *
	 * @param  void
	 * @return MantellaDB|boolean
	 */
	private function getDB() {
		$db = DBM::get($this->DATABASE);
		return ( ($db && $db->isConnected()) ? $db : false );
	}

}