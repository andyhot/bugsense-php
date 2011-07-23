<?php

class BugsenseRemote {
    /*
     * Does the actual sending of an exception
     */
    static function send_exception($exception) {
        $url = "/api/errors";
		$data = $exception->to_json();
		if (Bugsense::$debug) {
			echo print_r($data, true);
		}
		$data_encoded = 'data='.urlencode($data);
        self::call_remote($url, $data_encoded);
    }

    /*
     * Sends a POST request
     */
    static function call_remote($url, $post_data) {
        if (Bugsense::$use_ssl === true) {
            $s = fsockopen("ssl://".Bugsense::$host, 443, $errno, $errstr, 4);
        }
        else {
            $s = fsockopen(Bugsense::$host, 80, $errno, $errstr, 2);
        }

        if (!$s) {
            echo "[Error $errno] $errstr\n";
            return false;
        }

        $request  = "POST $url HTTP/1.1\r\n";
        $request .= "Host: ".Bugsense::$host."\r\n";
        $request .= "Accept: */*\r\n";
        $request .= "User-Agent: ".Bugsense::$client_name." ".Bugsense::$version."\r\n";
        $request .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $request .= "Connection: close\r\n";
        $request .= "Content-Length: ".strlen($post_data)."\r\n";
		$request .= "X-BugSense-Api-Key: ".Bugsense::$api_key."\r\n";
		$request .= "\r\n";
        $request .= "$post_data\r\n";
		
		if (Bugsense::$debug) {
			echo print_r($request, true);			
			return;
		}
        fwrite($s, $request);

        $response = "";
        while (!feof($s)) {
            $response .= fgets($s);
        }

        fclose($s);
		
		if (Bugsense::$debug_response)
			echo "Bugsense Response: ".$response;
    }

}
