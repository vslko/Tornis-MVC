<?php
class IndexController extends AbstractController {

    public function init( $R ) {
        parent::init($R);
    }



    // additional css styles
    public function doTheme_dot_css( $R ) {
        $txt = '/* ----- Theme ----- */' . "\n\n";
        $txt = $txt . "div.style_1 { color: white; }\n";
        $txt = $txt . "div.style_2 { color: red; }\n";
        $txt = $txt . "div.style_3 { color: green; }\n";
        $txt = $txt . "div.style_4 { color: yellow; }\n\n";
        $R->reply($txt, "text/css");
    }

    // on-page translations for javascript
    public function doIntl_dot_js( $R ) {
        $data 	= LNG::get('site');
        $result = "function _t( key ) {\n" .
            "  var JS_T = ".json_encode($data).";\n" .
            "  var kword = key.replace(/\s/g,'_');\n" .
            "  return ( JS_T[kword] == undefined ) ? key : JS_T[kword];\n" .
            "};\n" .
            "function _tl( key ) { return _t(key).toLowerCase(); };\n" .
            "function _tu( key ) { return _t(key).toUpperCase(); };\n" .
            "function _tf( key ) { return _t(key).charAt(0).toUpperCase() + _t(key).slice(1).toLowerCase(); };\n";
        $R->reply($result, "text/javascript");
    }


    // default entry point
    public function do_( $R ) {
        VIEW::template("index");
        VIEW::set('today', @date("Y-m-d"));
        VIEW::set('random', rand(100,999));
        VIEW::show(true, $this->EXTRAS);
    }



}