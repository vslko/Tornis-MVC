<?php

/**
 * Wrapper for exec wget
 */

class Wget {

    const DEFAULT_TIMEOUT = 5; // Seconds
    const DEFAULT_TRIES = 5; // Retries on timeout or failure

    protected $cmd = array('wget');
    protected $post = array();

    protected $output = '';
    protected $return = '';

    protected $timeout_overridden = false;
    protected $tries_overridden = false;

    /**
     * @return Wget
     */
    public function fake_browser() {
        $this->cmd[] = "--header='Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'";
        $this->cmd[] = "--header='Accept-Language: en-gb,en;q=0.5'";
        $this->cmd[] = "--header='Accept-Encoding: deflate'"; // Pls no gzip
        $this->cmd[] = "--header='Connection: keep-alive'";
        $this->cmd[] = "-U 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:18.0) Gecko/20100101 Firefox/18.0'";

        return $this;
    }

    public function get_output() {
        return $this->output;
    }

    public function cmd($cmd) {
        $this->cmd[] = $cmd;
        return $this;
    }

    public function header($var, $val) {
        $this->cmd[] = "--header='$var: $val'";
        return $this;
    }

    /**
     * @return Wget
     */
    public function load_cookies($file) {
        $this->cmd[] = "--load-cookies '$file'";
        return $this;
    }

    /**
     * @return Wget
     */
    public function save_cookies($file) {
        $this->cmd[] = "--save-cookies '$file'";
        return $this;
    }

    /**
     * @return Wget
     */
    public function keep_session_cookies() {
        $this->cmd[] = "--keep-session-cookies";
        return $this;
    }

    /**
     * @return Wget
     */
    public function save_to($file) {
        $this->cmd[] = "-O '$file'";
        return $this;
    }

    /**
     * @return Wget
     */
    public function post($key, $val = null) {
        $post = $key;
        if ($val !== null) {
            $post .= '='.$val;
        }

        $this->post[] = $post;
        return $this;
    }

    /**
     * @return Wget
     */
    public function silent() {
        $this->cmd[] = "-q";
        return $this;
    }

    /**
     * @return Wget
     */
    public function debug() {
        $this->cmd[] = "-d";
        return $this;
    }

    /**
     * @return Wget
     */
    public function server_response() {
        $this->cmd[] = "-S";
        return $this;
    }

    /**
     * @return Wget
     */
    public function accept_json() {
        $this->cmd[] = "--header='Accept: application/json'";
        return $this;
    }

    /**
     * @return Wget
     */
    public function content_type($type) {
        $this->cmd[] = "--header='Content-Type: ".$type."'";
        return $this;
    }

    /**
     * @return Wget
     */
    public function timeout($seconds) {
        $this->timeout_overridden = true;
        $this->cmd[] = "--timeout=".(int)$seconds;
        return $this;
    }

    public function referer($referer) {
        $this->cmd[] = "--referer=".escapeshellcmd($referer);
        return $this;
    }

    /**
     * @return Wget
     */
    public function tries($tries) {
        $this->tries_overridden = true;
        $this->cmd[] = "--tries=".(int)$tries;
        return $this;
    }

    /**
     * @return Wget
     */
    public function ignore_certificate() {
        $this->cmd[] = "--no-check-certificate";
        return $this;
    }

    /**
     * @return bool
     */
    public function exec($file) {
        if (count($this->post)) {
            $this->cmd[] = "--post-data '".implode('&', $this->post)."'";
        }

        if (!$this->timeout_overridden) {
            $this->timeout(self::DEFAULT_TIMEOUT);
        }

        if (!$this->tries_overridden) {
            $this->tries(self::DEFAULT_TRIES);
        }

        $this->cmd[] = "'$file'";

        $cmd_final = implode(' ', $this->cmd);

        die($cmd_final);
        exec($cmd_final, $this->output, $this->return);
        return $this->return;
    }
}
