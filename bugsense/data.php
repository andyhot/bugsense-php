<?php

class BugsenseData {

    protected $exception;
    protected $backtrace = array();

    function __construct(Exception $exception) {
        $this->exception = $exception;

        $trace = $this->exception->getTrace();
        foreach ($trace as $t) {
            if (!isset($t["file"])) continue;
            $this->backtrace[] = "$t[file]:$t[line]:in `$t[function]\'";
        }
		$where = "";
		if ($this->backtrace && count($this->backtrace)>0) {
			$where = $this->backtrace[0];
		}

        // environment data
        $data = BugsenseEnvironment::to_array();

        // exception data
        $message = $this->exception->getMessage();
        $now = date("c");

        // spoof 404 error
        $error_class = get_class($this->exception);
        if ($error_class == "Http404Error") {
            $error_class = "ActionController::UnknownAction";
        }

        $data["exception"] = array(
            "klass" => $error_class,
            "message" => $message,
            "backtrace" => $this->backtrace,
            "occurred_at" => $now,
			"where" => $where
        );

        // context
        $context = Bugsense::$context;
        if (!empty($context)) {
            $data["context"] = $context;
        }

        if (isset($_SERVER["HTTP_HOST"])) {

            // request data
            $session = isset($_SESSION) ? $_SESSION : array();

            // sanitize headers
            $headers = getallheaders();
            if (isset($headers["Cookie"])) {
              $headers["Cookie"] = preg_replace("/PHPSESSID=\S+/", "PHPSESSID=[FILTERED]", $headers["Cookie"]);
            }

            $server = $_SERVER;
            $keys = array("HTTPS", "HTTP_HOST", "REQUEST_URI", "REQUEST_METHOD", "REMOTE_ADDR");
            $this->fill_keys($server, $keys);

            $protocol = $server["HTTPS"] && $server["HTTPS"] != "off" ? "https://" : "http://";
            $url = $server["HTTP_HOST"] ? "$protocol$server[HTTP_HOST]$server[REQUEST_URI]" : "";

            $data["request"] = array(
                "url" => $url,
                "req_method" => strtolower($server["REQUEST_METHOD"]),
                "remote_ip" => $server["REMOTE_ADDR"],
                "headers" => $headers,
                "session" => $session,
				"parameters" => array()
            );

            if (!empty(Bugsense::$controller) && !empty(Bugsense::$action)) {
                $data["request"]["controller"] = Bugsense::$controller;
                $data["request"]["action"] = Bugsense::$action;
            }

            $params = array_merge($_GET, $_POST);
            if (!empty($params)) {
                $data["request"]["parameters"] = $params;
            }
        }

        $this->data = $data;
    }

    function uniqueness_hash() {
        return md5(implode("", $this->backtrace));
    }

    function to_json() {
        return json_encode($this->data);
    }

    function fill_keys(&$arr, $keys) {
        foreach ($keys as $key) {
            if (!isset($arr[$key])) {
                $arr[$key] = false;
            }
        }
    }

}

// http://php.net/manual/en/function.getallheaders.php
if (!function_exists("getallheaders")) {
    function getallheaders() {
        $headers = array();
        foreach ($_SERVER as $name => $value) {
           if (substr($name, 0, 5) == "HTTP_") {
               $headers[str_replace(" ", "-", ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
           }
        }
        return $headers;
    }
}
