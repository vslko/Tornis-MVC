<?php
class AbstractController extends MantellaController {

    public $EXTRAS;

    public function init($R) {

        // ---------- CSRF checking ----------
        if (!empty($_REQUEST["__csrf_token"]) && !hash_equals($_REQUEST["__csrf_token"], $_SESSION["__csrf_token"])) {
            $R->error(403, "Forbidden CSRR token. Requested \"".$_REQUEST["__csrf_token"]."\", Session \"".$_SESSION["__csrf_token"]."\"");
        }
        if (USE_CSRF_TOKEN == 'true' and !$R->isAjax()) {
            $_SESSION["__csrf_token"] = bin2hex(random_bytes(32));
        }

        // Localization
        $lang = strtolower( $R->getPrefix() );
        $lang = empty($lang) ? DEFAULT_LANGUAGE : $lang;
        if (LNG::is_available($lang) === False) { $R->redirect("//".$_SERVER["HTTP_HOST"]."/".DEFAULT_LANGUAGE."/"); }
        LNG::init($lang);


        // Define extra-variables for all template
        $this->EXTRAS = array(
            "language"      => $lang,
            "languages"     => LNG::languages(),
            "t"             => LNG::get('site'),
            "session"       => AUTH::logged(),
            "host"          => $_SERVER["HTTP_HOST"],
            "image_url"     => IMG_URL,
            "location"      => array(
                'page'      => strtolower($R->getController()),
                'action'    => strtolower($R->getAction()),
                'location'  => strtolower($R->getController())."/".strtolower($R->getAction())
            ),
            'canonical_link', strtolower($_SERVER["REQUEST_SCHEME"]."://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"])
        );


        // google authorization button
        $GOOGLE = new Google(GOOGLE_CLIENT_ID, GOOGLE_SECRET_KEY, $lang."/auth_google");
        VIEW::set('google_button_auth_url', $GOOGLE->get_button());

        // loggers
        LOG::create("actions", "{ERR}/actions/site_1_{YEAR}-{MONTH}-{DAY}.log", "rich", true);

        // logger for all database queries
        LOG::create("queries", "{ERR}/queries/site_1_{YEAR}-{MONTH}-{DAY}.log", "text", true);
        DBM::get('db')->setLogger( function($msg) { LOG::add($msg, "info", "queries"); } );

    }


    public function show_error_page($message) {
        VIEW::template("error");
        VIEW::set('message', $message);
        VIEW::show(true, $this->EXTRAS);
    }

    public function show_success_page($message) {
        VIEW::template("success");
        VIEW::set('message', $message);
        VIEW::show(true, $this->EXTRAS);
    }


}
