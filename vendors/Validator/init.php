<?php
$LIB_NAME = "MantellaValidator";
$LIB_ALIAS = "CHK";

require_once( dirname(__FILE__)."/MantellaValidator.php");
class_alias( $LIB_NAME, $LIB_ALIAS);
?>