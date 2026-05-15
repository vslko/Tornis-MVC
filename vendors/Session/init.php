<?php
/* 
parameters:
  session = "connection=db;table=users;login=login;password=secret;encoding=md5;fields=id,login,name,email,role" 
*/

$LIB_NAME = "Session";
$LIB_ALIAS = "AUTH";

require_once( dirname(__FILE__)."/MantellaSession.php");
class_alias('MantellaSession', $LIB_ALIAS);

$parameters = explode(";", CONF::get('vendor_'.$LIB_NAME));
$params = array();
foreach($parameters as $p) {
    $p = explode("=",trim($p));
    $params[ trim($p[0]) ] = trim( $p[1] );
}
AUTH::init( $params );

?>