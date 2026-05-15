<?php
$LIB_NAME = "Logger";
$LIB_ALIAS = "LOG";

require_once( dirname(__FILE__)."/MantellaLogger.php");
class_alias( "MantellaLogger", $LIB_ALIAS);

register_shutdown_function( function(){LOG::shutdown();} );

// -------- read params from config and create logs ----------
$logs = explode(";", CONF::get('vendor_'.$LIB_NAME));
foreach($logs as $l) {
    $param = explode(",", $l);
    if ($params and count($param)>=2) {
        LOG::create(
            trim($param[0]), // name
            trim($param[1]), // path
            isset($param[2]) ? trim($param[2]) : NULL, // mode
            isset($param[3]) ? (boolean)(int)trim($param[3]) : false // auto-flush
        );
    }
}

?>