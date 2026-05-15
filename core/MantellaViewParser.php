<?php
/**
 * MantellaMVC - A PHP Framework For Web Applications
 *
 * @class    MantellaViewParser
 * @version    1.4
 * @author    Vasilij Olhov <vsl@inbox.lv>
 */
final class MantellaViewParser {

    public static function parse( $html="" ){

        $html = preg_replace_callback('/\{INCLUDE ([^\n]+)\}/', 'self::helper_include' ,$html);

        $html = preg_replace_callback('/\{META ([^\n]+)\}/', 'self::helper_meta' ,$html);

		$html = str_replace("{HEAD}", "{HEAD }" ,$html);
        $html = preg_replace_callback('/\{HEAD([^\n]+)\}/', 'self::helper_head' ,$html);

        $html = str_replace("{TITLE}", "{TITLE }" ,$html);
        $html = preg_replace_callback('/\{TITLE([^\n]+)\}/', 'self::helper_title' ,$html);

        $html = preg_replace_callback('/\{SCRIPTS ([^\n]+)\}/', 'self::helper_scripts' ,$html);

        $html = preg_replace_callback('/\{STYLES ([^\n]+)\}/', 'self::helper_styles' ,$html);

        $html = preg_replace_callback("/\{PHP\}(.*)\{PHPEND\}/iUs" , 'self::helper_php' , $html);

        $html = preg_replace_callback('/\{IF ([^\n]+)\}(.*)\{ENDIF\}/iUs', 'self::helper_if' ,$html);

        $html = preg_replace_callback('/\{LOOP ([^\n]+)\}(.*)\{ENDLOOP\}/iUs', 'self::helper_loop' ,$html);

        $html = preg_replace_callback("/(\<\?php if\()([^\n]+)(\) \{ \?\>)/" , 'self::helper_ifvars' ,$html);

        $html = preg_replace_callback("/\!([A-Za-z0-9._\-\:\$]+)\!/" , 'self::helper_vars' ,$html);

        $html = preg_replace_callback('/\{CSRF_TOKEN\}/', 'self::helper_csrf' ,$html);

        return $html;
    }




    private function helper_include($matches) {
     	// search local view
        $name = realpath(M_APP_PATH."views") . DIRECTORY_SEPARATOR . $matches[1] . ".tmpl";
        // search shared view
        if (!file_exists($name)) { $name =  realpath(M_ROOT_PATH.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."shared".DIRECTORY_SEPARATOR."views").DIRECTORY_SEPARATOR.$matches[1].".tmpl"; }
        // return content if view has been found
        if (file_exists($name)) { return file_get_contents($name); }
    	return "";
    }




    private function helper_meta($matches) {
        $values = explode("=", $matches[1] );
        if ( strtolower(trim($values[0])) == "charset" ) {
        	return '<meta http-equiv="content-type" content="text/html; charset=' . (isset($values[1])?trim($values[1]):"utf-8" ) . '">';
        }
        else {
        	return '<meta name="'. trim($values[0]) .'" content="' . (isset($values[1]) ? trim($values[1]):" ") . '">';
        }
    }




    private function helper_head($matches) {
        $target = $matches[1];
        return "<head><title>" . ( (strlen(trim($target))>0) ? trim($target) : M_SITE_NAME ) . "</title></head>";
    }


    private function helper_title($matches) {
        $target = $matches[1];
        return "<title>" . ( (strlen(trim($target))>0) ? trim($target) : M_SITE_NAME ) . "</title>";
    }


    private function helper_scripts($matches) {
        $target = str_replace( array('"',"'") , '', $matches[1] );
        $scripts = explode(",",$target);
        if (!is_array($scripts)) { $scripts[0] = $scripts; }
        $ret = "";

        for ($i=0; $i<count($scripts); $i++) {
        	$ret .= "<script type=\"text/javascript\" src=\"".M_BASE_URL."js/".trim($scripts[$i]).".js\"></script>\n";
        }
        return $ret;
    }




    private function helper_styles($matches) {
        $target = str_replace( array('"',"'") , '', $matches[1] );
        $styles = explode(",",$target);
        if (!is_array($styles)) { $styles[0] = $styles; }
        $ret = "";
        for ($i=0; $i<count($styles); $i++) {
        	$ret .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"".M_BASE_URL."css/".trim($styles[$i]).".css\" />\n";
        }
        return $ret;
    }



    private function helper_php($matches) {
        return "<?php " . $matches[1] . " ?>";
    }


    private function helper_csrf($matches) {
        if ( array_key_exists("__csrf_token", $_SESSION) ) {
            return "<input type=\"hidden\" name=\"__csrf_token\" value=\"".$_SESSION['__csrf_token']."\" />\n";
        }
        else {
            return "";
        }

    }



    private function helper_if($matches) {
        $condition = trim($matches[1]);
        $condition = preg_replace("/\!([A-Za-z0-9_]+)\!/", "\$\$1", $condition );
        $content = $matches[2];
        $ret = "<?php if( " . (str_replace( array(" and ", " AND ", " not ", " NOT ", " or ", " OR ") , array(" && ", " && ", " !", " !", " || ", " || "), $condition)) . " ) { ?>";
        return $ret . str_replace( "{ELSE}", "<?php } else { ?>", $content) . "<?php } ?>";
    }




    private function helper_loop($matches) {
        $varname = trim($matches[1]);
        $varindex = "_".$varname."INDEX_";
        $content = $matches[2];
        $ret = "<?php for(\$".$varindex."=0; \$".$varindex."<count(\$".$varname."); \$".$varindex."++ ) { ?>\n";
        $ret .= str_replace( array("!".$varname."!", "!".$varname."." ) , array("!".$varname.".\$".$varindex."!", "!".$varname.".\$".$varindex."." ), $content );
        $ret .= "<?php } ?>";
        return $ret;
    }




    private function helper_ifvars($matches) {
    	$target = preg_replace_callback("/\!([A-Za-z0-9._\$]+)\!/", 'self::helper_ifvars_vars' , $matches[2] );
    	return $matches[1].$target.$matches[3];
    }

	private function helper_ifvars_vars($matches) {
    	$vars = explode(".",$matches[1]);
    	if (!is_array($vars)) { $vars[0] = $vars; }
    	$ret = "";
    	for ($i=0; $i<count($vars); $i++) {
    		$ret .= ( ($i==0) ? "\$".$vars[$i] : ( (strpos($vars[$i],"\$")===0) ? "[".$vars[$i]."]" : "['".$vars[$i]."']" )  );
    	}
    	return $ret;
	}



    private function helper_vars($matches) {
    	$vars = explode(".",$matches[1]);
    	if (!is_array($vars)) { $vars[0] = $vars; }
    	$ret = "";
    	for ($i=0; $i<count($vars); $i++) {
    		$ret .= ( ($i==0) ? "\$".$vars[$i] : ( (strpos($vars[$i],"\$")===0) ? "[".$vars[$i]."]" : "['".$vars[$i]."']" )  );
    	}
    	return "<?php echo ".$ret."; ?>";
    }


}

?>