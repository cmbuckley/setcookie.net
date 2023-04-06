<?php

class App {
    protected $main = 'setcookie.net';
    protected $server;

    public function __construct() {
        $this->server = $_SERVER;

        // pre-prod Fly app domain
        if (getenv('APP_ENV') != 'production' && getenv('FLY_APP_NAME')) {
            $this->main = getenv('FLY_APP_NAME') . '.fly.dev';
        }
    }

    // for helper pages like /quirks/
    // redirects to main domain
    public function mainIsCanonical() {
        if ($this->main !== $this->server['HTTP_HOST']) {
            $path = dirname($this->server['SCRIPT_NAME']);
            header("Location: https://{$this->main}$path/");
            exit;
        }
    }

    // get current domain
    public function getHost() {
        return $this->server['HTTP_HOST'];
    }

    // get top-level domain
    public function getMainHost() {
        return $this->main;
    }

    // get the requested URL
    public function getUrl() {
        return sprintf(
            'http%s://%s%s',
            $this->isHttps() ? 's' : '',
            $this->getHost(),
            $this->server['REQUEST_URI']
        );
    }

    // if the request is via https
    public function isHttps() {
        return (isset($this->server['HTTP_X_FORWARDED_SSL'])
            ? $this->server['HTTP_X_FORWARDED_SSL'] == 'on'
            : (isset($this->server['HTTPS']) && $this->server['HTTPS'] == 'on'));
    }

    // output domains that we can set this cookie on
    public function getDomains() {
        $domains = [".$this->main", $this->main];
        $host = $this->server['HTTP_HOST'];

        if ($host != $this->main && str_ends_with($host, $this->main)) {
            $subs = substr($host, 0, strpos($host, $this->main) - 1);
            $curr = ".$this->main";

            foreach (array_slice(array_reverse(explode('.', $subs)), 0, 2) as $sub) {
                array_unshift($domains, ".$sub$curr", "$sub$curr");
                $curr = ".$sub$curr";
            }
        }

        return $domains;
    }

    // get the Set-Cookie header that was sent
    public function getSentHeader() {
        return current(array_filter(headers_list(), function ($h) {
            return stripos($h, 'Set-Cookie') !== false;
        }));
    }

    // check if a cookie-path path-matches a request-path
    // https://httpwg.org/specs/rfc6265.html#cookie-path
    function pathMatch($cookiePath) {
        $requestPath = $this->server['REQUEST_URI'];

        if ($cookiePath === '' || $cookiePath === $requestPath) {
            return true;
        }

        // cookie-path is a prefix or the request-path, and either cookie-path ends with a "/",
        // or the first character not included in cookie-path is a "/"
        if (str_starts_with($requestPath, $cookiePath) &&
            (substr($cookiePath, -1) == '/' || substr($requestPath, strlen($cookiePath), 1) == '/')
        ) {
            return true;
        }

        return false;
    }
}
