<?php
$LIB_NAME = "MantellaCache";
$LIB_ALIAS = "CACHE";

require_once( dirname(__FILE__)."/MantellaCache.php");
class_alias( $LIB_NAME, $LIB_ALIAS);
?>