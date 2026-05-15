<?php
class Google {

    private $auth_url = "https://accounts.google.com/o/oauth2/auth"; // GOOGLE_AUTH_URI
    private $token_url = "https://accounts.google.com/o/oauth2/token"; // GOOGLE_TOKEN_URI
    private $userinfo_url = "https://www.googleapis.com/oauth2/v1/userinfo"; // GOOGLE_USER_INFO_URI
    private $scopes = array('https://www.googleapis.com/auth/userinfo.email','https://www.googleapis.com/auth/userinfo.profile'); // GOOGLE_SCOPES
    private $client_id = null; // GOOGLE_CLIENT_ID
    private $client_secret = null; // OOGLE_CLIENT_SECRET
    private $callback_url = null; // GOOGLE_REDIRECT_URI


    public function __construct($client, $secret, $return_address) {
        $this->client_id        = $client;
        $this->client_secret    = $secret;
        $this->callback_url     = strtolower($_SERVER["REQUEST_SCHEME"]."://".$_SERVER["HTTP_HOST"])."/".$return_address;
    }


    public function get_button() {
        if ( !$this->client_id ) { return false; }
        $parameters = [
            'redirect_uri'  => $this->callback_url,
            'response_type' => 'code',
            'client_id'     => $this->client_id,
            'scope'         => implode(' ', $this->scopes),
        ];
        $uri = $this->auth_url . '?' . http_build_query($parameters);
        return $uri;
    }


    public function get_user($code) {
        $parameters = array(
            'code'          => $code,
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri'  => $this->callback_url,
            "grant_type"    => "authorization_code",
        );
        $ch = curl_init( $this->token_url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        try {
            $res = json_decode($result, true);
            if ($httpCode != 200 OR !is_array($res) OR !empty($res['error']) OR !isset($res['access_token'])) {
                return false;
            }
            $token = $res['access_token'];
        }
        catch(Exception $e) {
            return false;
        }

        $ch = curl_init( $this->userinfo_url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$token) );
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        try {
            $res = json_decode($result, true);
            if ($httpCode != 200 OR !is_array($res) OR !empty($res['error']) OR !isset($res['id'])) {
                return false;
            }
            return array(
                'user_id'   => $res['id'],
                'username'  => (array_key_exists('name', $res)          ? $res['name']          : null),
                'firstname' => (array_key_exists('given_name', $res)    ? $res['given_name']    : null),
                'lastname'  => (array_key_exists('family_name', $res)   ? $res['family_name']   : null),
                'photo'     => (array_key_exists('picture', $res)       ? $res['picture']       : null),
                'lang'      => DEFAULT_LANGUAGE,
                'email'     => (array_key_exists('email', $res)         ? $res['email']         : null),
                'provider'  => "GOOGLE"
            );
        }
        catch(Exception $e) {
            return false;
        }

    }


}