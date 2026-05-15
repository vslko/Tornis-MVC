<?php
class Telegram {

    private $app;   // Bot name
    private $token; // Bot token


    public function __construct($app, $token) {
        $this->token    = $token;
        $this->app      = $app;
    }


    public function get_widget_button($language) {
        if ( !$this->app ) { return false; }
        $callback_url = strtolower( $_SERVER["REQUEST_SCHEME"]."://".$_SERVER["HTTP_HOST"]."/" ) . $language . "/tg-web-auth";
        return "<script async src=\"https://telegram.org/js/telegram-widget.js?22\" data-size=\"large\" data-telegram-login=\"".$this->app."\" data-auth-url=\"".$callback_url."\"></script>";
    }


    public function get_custom_button($callback_address, $icon, $label, $classes) {
        if ( !$this->app ) { return false; }
        $callback_url = strtolower( $_SERVER["REQUEST_SCHEME"]."://".$_SERVER["HTTP_HOST"]."/" ) . $callback_address;
        $result  = "<script async src=\"https://telegram.org/js/telegram-widget.js?22\"></script>\n";
        $result .= "<script>\n";
        $result .= "function auth_via_telegram() {" . "\n";
        $result .= "  window.Telegram.Login.auth(". "\n";
        $result .= "    { bot_id: '".explode(":",$this->token)[0]."', request_access: true },"."\n";
        $result .= "    (data) => {"."\n";
        $result .= "        if (!data) { /* do nothing */ }"."\n";
        $result .= "        else {" . "\n";
        $result .= "           let params = new URLSearchParams(data).toString();". "\n";
        $result .= "           window.location.href = '".$callback_url."?'+params; }". "\n";
        $result .= "    }" . "\n";
        $result .= "  );" . "\n";
        $result .= "}\n";
        $result .= "</script>\n";
        $result .= '<span onclick="return auth_via_telegram()" class="'.$classes.'">' . "\n";
        if ($icon) { $result .= '<img src="'.$icon.'" height="32">&nbsp;&nbsp;'; }
        $result .= $label. "\n";

        $result .= '</span>' . "\n";
        return $result;
    }



    public function check_bot_data($auth_data) {
        if (!array_key_exists('hash', $auth_data)) { return false; }
        $check_hash = $auth_data['hash'];
        unset($auth_data['hash']);
        ksort($auth_data);
        $data = implode("\n", array_map(
            function ($k, $v) { return "$k=$v"; },
            array_keys($auth_data),
            array_values($auth_data)
        ));
        $secret_key = hash_hmac('sha256', $this->token, "WebAppData", true);
        $hash = bin2hex(hash_hmac('sha256', $data, $secret_key, true));
        if (strcasecmp($hash, $check_hash) == 0) {
            $user_data = json_decode($auth_data['user'], true);
            return array(
                'user_id'   => $user_data['id'],
                'username'  => ( (array_key_exists('username', $user_data) and strlen($user_data['username'])>0 )       ? $user_data['username']    : null),
                'firstname' => ( (array_key_exists('first_name', $user_data) and strlen($user_data['first_name'])>0 )   ? $user_data['first_name']  : null),
                'lastname'  => ( (array_key_exists('last_name', $user_data) and strlen($user_data['last_name'])>0 )     ? $user_data['last_name']   : null),
                'photo'     => ( (array_key_exists('photo_url', $user_data) and strlen($user_data['photo_url'])>0 )     ? $user_data['photo_url']   : null),
                'lang'      => ( (array_key_exists('language_code', $user_data) and strlen($user_data['language_code'])>0 )     ? substr($user_data['language_code'],0,2)   : DEFAULT_LANGUAGE),
                'provider'  => "TELEGRAM"
            );
        }
        return false;
    }





    public function check_web_data($auth_data) {
        if (!array_key_exists('hash', $auth_data)) { return false; }
        $check_hash = $auth_data['hash'];
        unset($auth_data['hash']);
        $data_check_array = [];
        foreach ($auth_data as $key => $value) { $data_check_array[] = $key . '=' . $value; }
        sort($data_check_array);
        $data_check_string = implode("\n", $data_check_array);
        $secret_key = hash('sha256', $this->token, true);
        $hash = hash_hmac('sha256', $data_check_string, $secret_key);
        if (strcmp($hash, $check_hash) == 0) {
            return array(
                'user_id'   => $auth_data['id'],
                'username'  => ( !empty($auth_data['username'])    ? $auth_data['username']    : null ),
                'firstname' => ( !empty($auth_data['first_name'])  ? $auth_data['first_name']  : null ),
                'lastname'  => ( !empty($auth_data['last_name'])   ? $auth_data['last_name']   : null ),
                'photo'     => ( !empty($auth_data['photo_url'])   ? $auth_data['photo_url']   : null ),
                'lang'      => "en",
                'provider'  => "TELEGRAM"
            );
        }
        return false;
    }




    public function send_message($user_chat_id, $message) {
        $url = "https://api.telegram.org/bot".$this->token."/sendMessage";
        $payload = array(
            'chat_id'       => $user_chat_id,
            'parse_mode'    => "HTML",
            'text'          => $message,
        );
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        try {
            $res = json_decode($res, true);
        }
        catch(Exception $e) {
            return array(
                'success'   => false,
                'error'     => "#".$httpCode.": Parsing JSON error"
            );
        }

        if ( $httpCode != 200 ) {
            return array(
                'success'   => false,
                'error'     => "HTTP Code #".$httpCode.": ".$res["description"],
            );
        }

        return array(
            'success'   => true,
            'error'     => null
        );
    }


}