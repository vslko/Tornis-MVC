<?php
/**
 * MantellaMVC - A PHP Framework For Web Applications
 *
 * @class	MantellaLogger
 * @alias	LOG
 * @version	1.2
 * @author	Vasilij Olhov <vsl@inbox.lv>
 */
final class MantellaLogger {

    /**
     * Array of log types
     *
     * @var array
     */
    private static $types = array(
        "info"  => "INFO" ,
        "debug" => "DEBUG",
        "error" => "ERROR",
        "note"  => "NOTICE",
        "ok"    => "SUCCESS",
    );

    /**
     * Array of log modes
     *
     * @var array
     */
    private static $modes = array("text" , "rich" , "html" , "xml");


    /**
     * Array of files to logs append
     *
     * @var array
     */
    private static $files = array();


    /**
     * Default file logs to write (it's first created file)
     *
     * @var string
     */
    private static $default_file = null;

    /**
     * Define new log-file and open it
     *
     * @param	string	$name
     * @param	string	$path
     * $param   string  $mode
     * @return	boolean
     */
    public static function create($name, $path, $mode="text", $auto_flush=true ) {
        if (!array_key_exists($name, self::$files)) {
            $mode_index = array_search(strtolower($mode), self::$modes);
            $parsed_path = self::parsePath($path);
            self::$files[$name] = array(
                'path'          => $path,
                'parsed_path'   => $parsed_path,
                'mode'          => ($mode_index===false) ? self::$modes[0] : self::$modes[$mode_index],
                'auto_flush'    => $auto_flush,
                'messages'      => array(),
                'handler'       => $auto_flush ? self::createFile( $parsed_path ) : null, // if auto_flush then open file
            );
        }

        if (!self::$default_file) { self::$default_file = $name; }

        return ( !$auto_flush || self::$files[$name]['handler'] );

    }



    /**
     * Flush all message into file
     *
     * @param	string	$name
     * @return	int
     */
    public static function flush($name) {
        if (!array_key_exists($name, self::$files)) { return false; }

        if ( self::$files[$name]['auto_flush']==true || empty(self::$files[$name]['messages']) ) { return true; }

        $parsed_path = self::parsePath( self::$files[$name]['path'] );
        $handler = self::createFile( $parsed_path );
        if (!$handler) { return false; }

        $messages = implode("", self::$files[$name]['messages']);
        if ( !( @fwrite($handler,$messages) ) ) { return false; };
        @fclose( self::$files[$name]['handler'] );

        self::$files[$name]['messages'] = array();

        return true;
    }



    /**
     * Remove file by name from list of files
     *
     * @param	string	$name
     * @return	boolean
     */
    public static function remove($name) {
        if (!array_key_exists($name, self::$files)) { return false; }

        // flush all messages
        if (self::$files[$name]['auto_flush'] == false) {
            self::flush($name);
        }
        else { // if opened, then close file
            @fclose( self::$files[$name]['handler'] );
        }

        unset( self::$files[$name] );

        // if it was default file, then set next one
        if ( $name == self::$default_file ) {
            $keys = array_keys(self::$files);
            self::$default_file = isset($keys[0]) ? $keys[0] : null;
        }

        return true;
    }


    /**
     * Calling on script end by "register_shutdown_function" function -> flush and close all files
     *
     * @return	void
     */
    public static function shutdown() {
        foreach( self::$files as $name => $file ) {
            self::remove($name);
        }
    }



    /**
     * Add message to the file.
     * If file is null, then add into first initialized file.
     * If type is null, then type be INFO
     *
     * @param	string	$message
     * @param	string	$type
     * @param	string	$file
     * @return	boolean
     */
    public static function add($message=null, $type="info", $file=null) {
        // check file in list
        if (!$file && !self::$default_file) { return false; }
        $n = $file ? $file : self::$default_file;
        if (!array_key_exists($n, self::$files)) { return false; }

        // prepare message
        $message = self::decoreMessage($message, self::$files[$n]['mode'], $type);

        // add message to internal list and exit from function
        if ( self::$files[$n]['auto_flush'] == false ) {
            self::$files[$n]['messages'][] = $message;
            return true;
        }

        // check if path must be changed by filepath template
        $parsed_path = self::parsePath(self::$files[$n]['path']);
        if ( self::$files[$n]['parsed_path'] != $parsed_path ) {
            self::$files[$n]['parsed_path'] = $parsed_path;
            self::$files[$n]['handler'] = self::createFile( $parsed_path );
        }

        if ( !self::$files[$n]['handler'] ) { return false; }

        $result = @fwrite(self::$files[$n]['handler'], $message );

        return (boolean)$result;
    }



    /**
     * Magic: Add message to the file by call Logger::_<file name>(message, type).
     * If type is null, then type be INFO
     *
     * @param	string	$message
     * @param	string	$type
     * @return	boolean
     */
    public static function __callStatic($name, $args) {
        $text = isset($args[0]) ? $args[0] : null;
        $type = isset($args[1]) ? $args[1] : null;
        $name = substr($name, 1);
        return self::add( $text , $type , $name);
    }





    private static function decoreMessage($message, $mode, $type) {
        $date       = @date('d.m.Y H:i:s');
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $url        = $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
        $method     = $_SERVER["REQUEST_METHOD"];

        $type = strtolower($type);
        $type = array_key_exists($type, self::$types) ? self::$types[$type] : self::$types['info'];

        switch ($mode) {

            case 'rich':
                $h = $date."\t".$type."\tFrom:".$ip_address . "\t".$method.":".$url."\n";
                return ( $h . $message . "\n\n" );
                break;

            case 'html':
                switch ($type) {
                    case 'INFORMATION': $type_color = "#3c763d"; break;
                    case 'SUCCESS': $type_color = "#00FF00"; break;
                    case 'ERROR': $type_color = "#a94442"; break;
                    case 'NOTIFICATION': $type_color = "#31708f"; break;
                    default: $type_color = "#777";
                }
                $t = "<tr style='color:".$type_color."'>".
                     "<td prop='log_date'>".$date."</td>".
                     "<td prop='log_method'>".$method."</td>".
                     "<td prop='log_url'>".$url."</td>".
                     "<td prop='log_ip_addr'>".$ip_address."</td>".
                     "<td prop='log_type'>".$type."</td>".
                     "<td prop='log_message'>".$message."</td>".
                     "</tr>\n";
                return $t;
                break;

            case 'xml':
                $t = "<log>" .
                     "<date>".$date."</date>" .
                     "<method>".$method."</method>" .
                     "<url>".$url."</url>" .
                     "<ip_address>".$ip_address."</ip_address>".
                     "<type>".$type."</type>" .
                     "<message>".$message."</message>" .
                     "</log>\n";
                return $t;

            default: // "text" too
                return ($date . "\t" . $type . "\t" . $message . "\n");

        }

    }





    private static function parsePath($path) {
        $fp = str_ireplace(
            array( "{APP}"              , "{ERR}"    ,                   "{WEB}",               "{YEAR}" ,  "{MONTH}",  '{DAY}',    '{HOUR}',  '{MINUTE}' ),
            array( realpath(M_APP_PATH) , realpath(dirname(M_ERR_PATH)), realpath(M_ROOT_PATH), @date('Y'), @date('m'), @date('d'), @date('H'), @date('i') ),
            $path
        );
        $fp = preg_replace_callback(
            "/{(.*)}/iUs",
            function ($matches) { return @date($matches[1]); },
            $fp
        );
        return $fp;
    }



    private static function createFile( $path ) {
        $path = str_replace( array("\\","/") , DIRECTORY_SEPARATOR , $path);
        $dir = dirname($path);

        if ( !file_exists($dir) ) {
            if ( !mkdir($dir, 0777, true) ) {
                return null;
            }
        }
        $FP = @fopen($path, 'a');
        return $FP ? $FP : null;
    }


}
