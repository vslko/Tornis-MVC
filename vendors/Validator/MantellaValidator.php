<?php
/**
 * MantellaMVC - A PHP Framework For Web Applications
 *
 * @class    MantellaValidator
 * @alias    CHK
 * @version    1.2
 * @author    Vasilij Olhov <vsl@inbox.lv>
 */
final class MantellaValidator {

    /**
	 * Set of validation results
	 *
	 * @var array
	 */
	private static $_checkers = array();


    /**
	 * Set of default validation rules messages
	 *
	 * @var array
	 */
    private static $_messages = array(
    									'success'			=> "Valid",
    									'not_found'			=> "Rule not found",
    									'required' 			=> "Field \"{field}\" is required",
    									'required_if' 		=> "Field \"{field}\" is required accourding field \"{params}\"",
    									'email'				=> "E-mail is not valid",
    									'url'				=> "URL is not valid",
    									'is'				=> "Field \"{field}\" is not equal {params}",
    									'integer'			=> "Field \"{field}\" is not integer",
    									'limits'			=> "Field \"{field}\" is out of range",
    									'max'				=> "Field's \"{field}\" value is too big",
    									'min'				=> "Field's \"{field}\" value is too small",
    									'sizes'				=> "Field's \"{field}\" length is out of range",
    									'size'				=> "Field's \"{field}\" length is incorrect",
    									'letters'			=> "In field \"{field}\" available only letters",
    									'digits'			=> "In field \"{field}\" available only digits",
										'allow'				=> "Field's \"{field}\" value is forbidden",
    									'deny'				=> "Field's \"{field}\" value is forbidden",
                                        'equal'				=> "Fields \"{field}\" and \"{params}\" are not equals",
                                        'different'			=> "Fields \"{field}\" and \"{params}\" are equals",
                                        'hex'				=> "Field \"{field}\" is not hex",
                                        'regexp'			=> "Field \"{field}\" is not valid",
                                        'simple_password'	=> "Password is too simple",
                                        'medium_password'	=> "Password is too simple",
                                        'strong_password'	=> "Password is too simple",
                                        'ip'				=> "Field \"{field}\" is not IP-address"
    );

    /**
	 * Set of custom validation rules messages
	 *
	 * @var array
	 */
    private static $_custom_messages = array();



	/**
	 * Execute validation
	 *
	 * @param	array	$input
	 * @param	array	$rules
	 * @param	array	$custom_errors
	 * @return	boolean
	 */
	public static function check($input, $rules, $custom_errors=null) {
        // save custom errors
        self::$_custom_messages = ( is_array($custom_errors) ) ? $custom_errors : array();

        // prepare rules for checking
        self::prepareRules($input, $rules);

        // let's check
        $result = true;
        for($i=0; $i<count(self::$_checkers); $i++) {
        	if ( !is_null(self::$_checkers[$i]['descr']) ) {
        		$result = false;
        		continue;
        	}

        	$func = "check_" . self::$_checkers[$i]['rule'];
        	self::$_checkers[$i]['valid'] = self::$func( $input , self::$_checkers[$i]['field'], self::$_checkers[$i]['params'] );
        	$result = self::$_checkers[$i]['valid'] ? $result : false;

        	self::$_checkers[$i]['descr'] = self::setMessage( self::$_checkers[$i] ) ;
        }

		return $result;

	}


	/**
	 * Get list of errors after validation
	 *
	 * @param	void
	 * @return	array
	 */
    public static function getUnvalidated() {
    	$ret = array();
    	for($i=0; $i<count(self::$_checkers); $i++) {
    		if( self::$_checkers[$i]['valid'] == false ) {
    			$ret[] = array(
    							'name' 	=> self::$_checkers[$i]['field'],
    							'value' => self::$_checkers[$i]['value'],
    							'rule'	=> self::$_checkers[$i]['rule'] . ( !empty(self::$_checkers[$i]['params']) ? ":".self::$_checkers[$i]['params'] : "" ),
    							'error' => self::$_checkers[$i]['descr']
    						  );
    		}
    	}
    	return $ret;
    }



	/**
	 * Function only for private use inside class
	 */
	private static function prepareRules($input , $rules ) {
		self::$_checkers = array();

        foreach($rules as $name => $rule) {

            $rules_array = explode("|",$rule);

            foreach($rules_array as $one_rule) {

                $composite_rule = explode(":",$one_rule);
                $ruleName = $composite_rule[0];
                $ruleParams = isset($composite_rule[1]) ? $composite_rule[1] : null;

                self::$_checkers[] = array(
            		'field' => $name,
            		'value'	=> ( isset($input[$name]) ? $input[$name]: null),
            		'rule'	=> $ruleName,
            		'params'=> $ruleParams,
            		'valid'	=> false,
            		'descr'	=> ( method_exists( __CLASS__ ,"check_".$ruleName) ? null : self::$_messages['not_found'] ),
            	);

            }

		}
	}

	/**
	 * Function only for private use inside class
	 */
	private static function setMessage($check) {
 		if ( $check['valid'] == true ) { return self::$_messages['success']; }

        $message = isset( self::$_custom_messages[ $check['field'].':'.$check['rule'] ] ) ? self::$_custom_messages[ $check['field'].':'.$check['rule'] ]
		           : ( isset( self::$_custom_messages[ $check['field'] ] ) ? self::$_custom_messages[ $check['field'] ] : self::$_messages[ $check['rule'] ] );

        $message = str_replace(
        						array( "{field}", 		"{value}", 		 "{params}"		  ),
        						array( $check['field'], $check['value'], $check['params'] ),
        						$message
        					  );
    	return $message;
	}

	/**
	 * Function only for private use inside class
	 */
	private static function getSafeValue($data, $field) {
		return ( isset($data[$field]) ? $data[$field] : null );
	}

	/**
	 * Function only for private use inside class
	 */
	private static function check_required($input=null, $field=null, $params=null) {
    	$value = self::getSafeValue($input, $field);
    	return ( !is_null($value) && strlen($value)>0 );
	}

	/**
	 * Function only for private use inside class
	 */
	private static function check_required_if($input=null, $field=null, $params=null) {
    	$value = self::getSafeValue($input, $field);
    	if ( !self::check_required($input,$params) ) return true;
    	return ( !is_null($value) && strlen($value)>0 );
	}

	/**
	 * Function only for private use inside class
	 */
	private static function check_email($input=null, $field=null, $params=null) {
    	//$pattern = "/^[_a-z0-9-]+(.[_a-z0-9-]+)*@[a-z0-9-]+(.[a-z0-9-]+)*(.[a-z]{2,3})$/i";
    	$value = self::getSafeValue($input, $field);
    	return ( filter_var($value, FILTER_VALIDATE_EMAIL) !== false);
	}

	/**
	 * Function only for private use inside class
	 */
	private static function check_url($input=null, $field=null, $params=null) {
    	$value = self::getSafeValue($input, $field);
    	return ( filter_var($value, FILTER_VALIDATE_URL) !== false);
	}

	/**
	 * Function only for private use inside class
	 */
	private static function check_is($input=null, $field=null, $params=null) {
    	$value = mb_strtolower(self::getSafeValue($input, $field),'UTF-8');
    	$value2 = mb_strtolower($params,'UTF-8');
    	return ( $value == $value2 );
	}

	/**
	 * Function only for private use inside class
	 */
	private static function check_integer($input=null, $field=null, $params=null) {
    	$value = self::getSafeValue($input, $field);
    	return ( filter_var($value, FILTER_VALIDATE_INT) !== false );
	}

	/**
	 * Function only for private use inside class
	 */
	private static function check_limits($input=null, $field=null, $params=null) {
		$value = self::getSafeValue($input, $field);
		$limits = explode(",",$params);
		$min = isset($limits[0]) ? ( ($limits[0]=="*") ? ~PHP_INT_MAX : floatval($limits[0]) ) : ~PHP_INT_MAX;
		$max = isset($limits[1]) ? ( ($limits[1]=="*") ? PHP_INT_MAX : floatval($limits[1]) ) : PHP_INT_MAX;
		return ( $min<=floatval($value) && floatval($value)<=$max );
	}

	/**
	 * Function only for private use inside class
	 */
  	private static function check_max($input=null, $field=null, $params=null) {
		$value = self::getSafeValue($input, $field);
	    return (floatval($value) <= floatval($params));
	}

	/**
	 * Function only for private use inside class
	 */
  	private static function check_min($input=null, $field=null, $params=null) {
		$value = self::getSafeValue($input, $field);
	    return (floatval($value) >= floatval($params));
	}

	/**
	 * Function only for private use inside class
	 */
	private static function check_size($input=null, $field=null, $params=null) {
		$value = self::getSafeValue($input, $field);
		return ( mb_strlen($value,'UTF-8') == intval($params) );
	}

	/**
	 * Function only for private use inside class
	 */
	private static function check_sizes($input=null, $field=null, $params=null) {
		$value = self::getSafeValue($input, $field);
		$sizes = explode(",",$params);
		$min = isset($sizes[0]) ? ( ($sizes[0]=="*") ? 0 : intval($sizes[0]) ) : 0;
		$max = isset($sizes[1]) ? ( ($sizes[1]=="*") ? PHP_INT_MAX : intval($sizes[1]) ) : PHP_INT_MAX;
		return ( $min<=mb_strlen($value,'UTF-8') && mb_strlen($value,'UTF-8')<=$max ) ;
	}

	/**
	 * Function only for private use inside class
	 */
    private static function check_letters($input=null, $field=null, $params=null) {
		$value = self::getSafeValue($input, $field);
		return ( (preg_match("/^[a-zA-Z]+$/", $value) == 1) );
	}


	/**
	 * Function only for private use inside class
	 */
	private static function check_digits($input=null, $field=null, $params=null) {
		$value = self::getSafeValue($input, $field);
		return ( (preg_match("/^[0-9]+$/", $value) == 1) );
	}


	/**
	 * Function only for private use inside class
	 */
    private static function check_allow($input=null, $field=null, $params=null) {
    	$value = mb_strtolower( self::getSafeValue($input, $field),'UTF-8' );
    	$words = explode(",",$params);
    	$words_list = array_map( function($item) { return mb_strtolower($item,'UTF-8'); }, $words);
    	return ( (in_array($value,$words_list)) );
    }

	/**
	 * Function only for private use inside class
	 */
    private static function check_deny($input=null, $field=null, $params=null) {
    	return ( !(self::check_allow($input, $field, $params)) );
    }

	/**
	 * Function only for private use inside class
	 */
	private static function check_equal($input=null, $field=null, $params=null) {
    	$value = mb_strtolower( self::getSafeValue($input, $field),'UTF-8' );
    	$value2 = mb_strtolower( $params,'UTF-8' );
    	return ( $value == $value2 );
	}

	/**
	 * Function only for private use inside class
	 */
	private static function check_different($input=null, $field=null, $params=null) {
    	return ( !(self::check_equal($input, $field, $params)) );
	}

	/**
	 * Function only for private use inside class
	 */
	private static function check_hex($input=null, $field=null, $params=null) {
		$value = self::getSafeValue($input, $field);
		return ( (preg_match("/^[A-Fa-f0-9]+$/", $value) == 1) );
	}

	/**
	 * Function only for private use inside class
	 */
	private static function check_regexp($input=null, $field=null, $params=null) {
		$value = self::getSafeValue($input, $field);
		return ( (preg_match($params, $value) == 1) );
	}


	/**
	 * Function only for private use inside class
	 * -- minimum 6 symbols; exists symbols and digits
	 */
	private static function check_simple_password($input=null, $field=null, $params=null) {
		$value = self::getSafeValue($input, $field);
        return ( preg_match("/([0-9]+)/", $value) &&
        		 preg_match("/([a-zA-Z]+)/", $value) &&
        		 self::check_sizes($input, $field, "6,*")
        	   );
	}

	/**
	 * Function only for private use inside class
	 * --  minimum 8 symbols, exists symbols, digits and specials chars
	 */
	private static function check_medium_password($input=null, $field=null, $params=null) {
		$value = self::getSafeValue($input, $field);
        return ( preg_match("/([0-9]+)/", $value) &&
        		 preg_match("/([a-zA-Z]+)/", $value) &&
        		 preg_match("/\W/", $value) &&
        		 self::check_sizes($input, $field, "8,*")
        	   );
	}

	/**
	 * Function only for private use inside class
	 * --  minimum 10 symbols, exists symbols in upper case, symbols in lower case, digits and specials chars
	 */
	private static function check_strong_password($input=null, $field=null, $params=null) {
		$value = self::getSafeValue($input, $field);
        return ( preg_match("/([0-9]+)/", $value) &&
        		 preg_match("/([a-z]+)/", $value) &&
        		 preg_match("/([A-Z]+)/", $value) &&
        		 preg_match("/\W/", $value) &&
        		 self::check_sizes($input, $field, "8,*")
        	   );
	}


	/**
	 * Function only for private use inside class
	 */
	private static function check_ip($input=null, $field=null, $params=null) {
    	$value = self::getSafeValue($input, $field);
    	return ( filter_var($value, FILTER_VALIDATE_IP) !== false);
	}

}
