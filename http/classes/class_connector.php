<?php
# $Id: class_connector.php 10237 2019-09-06 08:52:38Z armin11 $
# http://www.mapbender.org/index.php/class_connector
# Copyright (C) 2002 CCGIS
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2, or (at your option)
# any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.

require_once(dirname(__FILE__)."/../../core/globalSettings.php");
/**
 * Establishes a connection to a given URL (and loads the content).
 * Supports HTTP (GET and POST), cURL and socket connections.
 *
 * @class
 */
class connector {

	// URL patterns to block and log
	private static $blockedUrlPatterns = array(
		'version=1.4.0',
		
		// Intelligent patterns for malicious URLs
		'&http://',           // URL injection after parameter
		'&https://',          // URL injection after parameter
		'../',  
		'%2e%2e%2f',         // URL-encoded directory traversal
		'%2e%2e/',           // Mixed encoded directory traversal
		
		// Malicious domain patterns 
		'xn--',              // IDN-encoded domains (often malicious)
		'.pw/',              // Suspicious TLD (Palau, often abused)
		'sul.pw',            // Specific malicious domain
		'txtpdf.pro',        // Fake PDF service domain
		'qfkavl.',           // Random subdomain pattern
		'frbejqcdku',        // Random path pattern
		'ridfq53ry',         // Random alphanumeric pattern
		'cuxhorvyw.htm',     // Random filename pattern
	);
	
	// Enable/disable blocking functionality
	private static $enableBlocking = true;
	
	// Enable/disable logging functionality  
	private static $enableLogging = true;
	
	// Enable/disable debug logging of ALL requests (for testing)
	private static $enableDebugLogging = false;

	var $file;
	private $connectionType;
	public  $timeOut = 20;
	private $executionTimeOut = 0; //set max execution time in ms e.g. for a download process 
	private $httpType = "get";
	private $httpVersion = "1.0";
	private $httpPostData;
	private $httpContentType;
	private $httpPostFieldsNumber;
	private $curlSendCustomHeaders = true; //decide to send own headers or not
	private $curlSessionCookie = false;
	private $externalHeaders = "";
	public $httpCode = null;
	public $curlError = false;


	/**
	 * @constructor
	 * @param String url the URL that will be loaded (optional)
	 */
	public function __construct() {
		$this->set("connectionType", CONNECTION);
		if (func_num_args() == 1) {
			$url = func_get_arg(0);
			if ($url) {
				$this->load($url);
			}
		}
		else if (func_num_args() == 2) {
       		$auth = func_get_arg(1);
			$url = func_get_arg(0);
			if ($url) {
				$this->load($url,$auth);
			}
		}
	}

	/**
	 * Configure blocked URL patterns and settings
	 * @param array $patterns Array of patterns to block
	 * @param bool $enableBlocking Enable/disable blocking functionality
	 * @param bool $enableLogging Enable/disable logging functionality
	 * @param bool $enableDebugLogging Enable/disable debug logging of all requests
	 */
	public static function configureBlockedPatterns($patterns = null, $enableBlocking = null, $enableLogging = null, $enableDebugLogging = null) {
		if ($patterns !== null) {
			self::$blockedUrlPatterns = is_array($patterns) ? $patterns : array($patterns);
		}
		if ($enableBlocking !== null) {
			self::$enableBlocking = (bool)$enableBlocking;
		}
		if ($enableLogging !== null) {
			self::$enableLogging = (bool)$enableLogging;
		}
		if ($enableDebugLogging !== null) {
			self::$enableDebugLogging = (bool)$enableDebugLogging;
		}
	}

	/**
	 * Get current blocked URL patterns configuration
	 * @return array Current configuration
	 */
	public static function getBlockedPatternsConfig() {
		return array(
			'patterns' => self::$blockedUrlPatterns,
			'blocking_enabled' => self::$enableBlocking,
			'logging_enabled' => self::$enableLogging,
			'debug_logging_enabled' => self::$enableDebugLogging
		);
	}

	/**
	 * Loads content from the given URL.
	 */
	public function load($url) {
		//some firewalls have problems to allow requests from a server behind it to the same server through
		//an internet connection. It can be that some requests are done thru this class to the urls of
		//HTTP_AUTH_PROXY or OWSPROXY. If some of those are part of the url they must be exchanged with 127.0.0.1 - 			//which hopefully should work.
		$testMatch = $url;
		$localTmpFolder = 'file://'.str_replace('classes',ltrim(TMPDIR,'\.\./'),dirname(__FILE__)).'/';
		$pattern = '/^http:|https:|'.str_replace('/','\/',$localTmpFolder).'/';
		//$e = new mb_exception('file://'.str_replace('classes',ltrim(TMPDIR,'../'),dirname(__FILE__)).'/');
 		if (!preg_match($pattern,$testMatch)){
			$e = new mb_exception('classes/class_connector.php: Access to resource not allowed!');
			return false;
		}

		// Check for blocked URL patterns - this will throw exception if blocked
		if (!$this->checkBlockedUrlPatterns($url)) {
			return false;
		}
		//TODO: check if http is ok for all
		$posPROXY = strpos($url,OWSPROXY);
		//$e = new mb_exception('class_connector: old url: '.$url);
 		if($posPROXY !== false && OWSPROXY_USE_LOCALHOST == true){

			$e = new mb_notice('class_connector: old url: '.$url);
			$url = str_replace($_SERVER['HTTP_HOST'], "127.0.0.1", $url);
			$url = str_replace("https", "http", $url);//localhost no https should needed - it will be faster without
			$e = new mb_notice('class_connector: new url: '.$url);
		}

		$e = new mb_notice('class_connector: load url: '.$url);
		if (!$url) {

			$e = new mb_exception("connector: no URL given");
			return false;
		}
		switch ($this->connectionType) {
			case "curl":

			    if (func_num_args() == 2) {
            			$auth = func_get_arg(1);
						if (isset($auth)) {
							$e = new mb_notice("connector: curl auth");
							$this->file = $this->getCURL($url,$auth);
						}
				}
				else {

				$e = new mb_notice("connector: curl without auth");
					$this->file = $this->getCURL($url);
				}
				break;
			case "http":

			$e = new mb_notice("connector: http");
				$this->file = $this->getHTTP($url);
				break;
			case "socket":

			$e = new mb_notice("connector: socket");
				$this->file = $this->getSOCKET($url);
				break;
		}
		if(!$this->file){
			$e = new mb_exception("connector: unable to load: ".$url);
			return false;
		}
		return $this->file;
	}

	/**
	 * Sets the environment variables. The following can be set:
	 * - connectionType ("http", "curl", "socket")
	 * - httpType ("get", "post")
	 * - etc.
	 */
	public function set ($key, $value) {
		switch ($key) {
			case "connectionType":
				if ($this->isValidConnectionType($value)) {
					$this->connectionType = $value;
				}
				break;

			case "httpVersion":
				if (in_array($value, array("1.0", "1.1"))) {
					$this->httpVersion = $value;
				}
				else {
					$e = new mb_exception("class_connector.php: invalid http type '" . $value . "'");
				}
				break;

			case "httpType":
				if (in_array(mb_strtoupper($value), array("POST", "GET"))) {
					$this->httpType = $value;
				}
				else {
					$e = new mb_exception("class_connector.php: invalid http type '" . $value . "'");
				}
				break;

			case "httpPostData":
				$this->httpPostData = $value;
				break;

			case "httpPostFieldsNumber":
				$this->httpPostFieldsNumber = $value;
				break;

			case "curlSendCustomHeaders":
				$this->curlSendCustomHeaders = $value;
				break;

			case "timeOut":
				$this->timeOut = (integer)$value;
				break;

			case "executionTimeOut":
			       $this->executionTimeOut = (integer)$value;
			       break;
				
			case "externalHeaders":
				$this->externalHeaders = $value;
				break;

			case "curlSessionCookie":
				$this->curlSessionCookie = $value;
				break;

			case "httpContentType":
				if ($this->isValidHttpContentType($value)) {
					$this->httpContentType = $value;
				}
				break;
		}
	}

	private function isValidConnectionType ($value) {
		if (in_array(mb_strtoupper($value), array("HTTP", "CURL", "SOCKET"))) {
			return true;
		}
		else {
			$e = new mb_exception("class_connector.php: invalid connection type '" . $value . "'");
			return false;
		}
	}

	private function isValidHttpContentType ($value) {
		$validHttpContentTypeArray = array("XML","TEXT/XML","APPLICATION/XML","MULTIPART/FORM-DATA");
		if (in_array(mb_strtoupper($value), $validHttpContentTypeArray)) {
			switch (mb_strtoupper($value)) {
				case "XML":
					$this->httpContentType = "application/xml";
					break;
			}
			return true;
		}
		else {
			$e = new mb_exception("class_connector.php: invalid HTTP content type '" . $value . "'");
			return false;
		}
	}

	/**
	 * Check if URL contains blocked patterns and handle accordingly
	 * @param string $url The URL to check
	 * @return bool Returns false if URL should be blocked, true otherwise
	 */
	private function checkBlockedUrlPatterns($url) {
		// Debug logging for ALL requests (if enabled)
		if (self::$enableDebugLogging) {
			$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			$caller = $this->getCallerInfo($backtrace);
			$this->logAllUrlAccess($url, $caller);
		}

		if (!self::$enableBlocking && !self::$enableLogging) {
			return true;
		}

		// Check for multiple question marks (URL injection pattern)
		if (substr_count($url, '?') > 1) {
			$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			$caller = $this->getCallerInfo($backtrace);
			
			if (self::$enableLogging) {
				$this->logBlockedUrlAccess($url, 'multiple_question_marks', $caller, $backtrace);
			}
			
			if (self::$enableBlocking) {
				$this->throwBlockedUrlException($url, 'multiple_question_marks', $caller);
				return false;
			}
		}

		foreach (self::$blockedUrlPatterns as $pattern) {
			if (strpos($url, $pattern) !== false) {
				// Get stack trace to identify calling script
				$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
				$caller = $this->getCallerInfo($backtrace);
				
				if (self::$enableLogging) {
					$this->logBlockedUrlAccess($url, $pattern, $caller, $backtrace);
				}
				
				if (self::$enableBlocking) {
					$this->throwBlockedUrlException($url, $pattern, $caller);
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Extract caller information from backtrace
	 * @param array $backtrace The debug backtrace
	 * @return array Caller information
	 */
	private function getCallerInfo($backtrace) {
		// Find the first non-connector class in the stack
		foreach ($backtrace as $trace) {
			if (isset($trace['file']) && 
				strpos($trace['file'], 'class_connector.php') === false) {
				return array(
					'file' => $trace['file'],
					'line' => isset($trace['line']) ? $trace['line'] : 'unknown',
					'function' => isset($trace['function']) ? $trace['function'] : 'unknown',
					'class' => isset($trace['class']) ? $trace['class'] : 'unknown'
				);
			}
		}
		return array(
			'file' => 'unknown',
			'line' => 'unknown', 
			'function' => 'unknown',
			'class' => 'unknown'
		);
	}

	/**
	 * Log blocked URL access attempt
	 * @param string $url The blocked URL
	 * @param string $pattern The matched pattern
	 * @param array $caller Caller information
	 * @param array $backtrace Full backtrace
	 */
	private function logBlockedUrlAccess($url, $pattern, $caller, $backtrace) {
		$action = self::$enableBlocking ? "BLOCKED" : "DETECTED";
		$logMessage = "SECURITY PATTERN $action:\n";
		$logMessage .= "URL: " . $url . "\n";
		$logMessage .= "Matched Pattern: " . $pattern . "\n";
		$logMessage .= "Action: " . (self::$enableBlocking ? "Blocked" : "Logged only") . "\n";
		$logMessage .= "Calling Script: " . $caller['file'] . " (Line: " . $caller['line'] . ")\n";
		$logMessage .= "Calling Function: " . $caller['class'] . "::" . $caller['function'] . "\n";
		$logMessage .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
		$logMessage .= "Stack Trace:\n";
		
		foreach ($backtrace as $i => $trace) {
			if (isset($trace['file'])) {
				$logMessage .= "#$i " . $trace['file'];
				if (isset($trace['line'])) {
					$logMessage .= "(" . $trace['line'] . ")";
				}
				if (isset($trace['class']) && isset($trace['function'])) {
					$logMessage .= ": " . $trace['class'] . "::" . $trace['function'] . "()";
				} elseif (isset($trace['function'])) {
					$logMessage .= ": " . $trace['function'] . "()";
				}
				$logMessage .= "\n";
			}
		}
		$logMessage .= "----------------------------------------\n";
		
		// Write to unified security log file
		$this->writeToSecurityLogFile($logMessage);
	}

	/**
	 * Write security event to unified log file
	 * @param string $message The message to log
	 */
	private function writeToSecurityLogFile($message) {
		$logFile = dirname(__FILE__) . '/../../log/security_patterns.log';
		$logDir = dirname($logFile);
		
		// Create log directory if it doesn't exist
		if (!is_dir($logDir)) {
			@mkdir($logDir, 0755, true);
		}
		
		// Write to log file with secure permissions
		@file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
		@chmod($logFile, 0640); // Readable only by owner and group
	}

	/**
	 * Log all URL access for debugging purposes
	 * @param string $url The requested URL
	 * @param array $caller Caller information
	 */
	private function logAllUrlAccess($url, $caller) {
		$logMessage = "ALL URL ACCESS LOG:\n";
		$logMessage .= "URL: " . $url . "\n";
		$logMessage .= "Calling Script: " . $caller['file'] . " (Line: " . $caller['line'] . ")\n";
		$logMessage .= "Calling Function: " . $caller['class'] . "::" . $caller['function'] . "\n";
		$logMessage .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
		$logMessage .= "----------------------------------------\n";
		
		// Write to separate debug log file
		$this->writeToDebugLogFile($logMessage);
	}

	/**
	 * Write debug log message to separate file
	 * @param string $message The message to log
	 */
	private function writeToDebugLogFile($message) {
		$logFile = dirname(__FILE__) . '/../../log/debug_all_requests.log';
		$logDir = dirname($logFile);
		
		// Create log directory if it doesn't exist
		if (!is_dir($logDir) && is_writable(dirname($logDir))) {
			@mkdir($logDir, 0755, true);
		}
		
		// Write to log file with secure permissions
		@file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
		@chmod($logFile, 0640); // Readable only by owner and group
	}

	/**
	 * Throw exception for blocked URL access
	 * @param string $url The blocked URL
	 * @param string $pattern The matched pattern
	 * @param array $caller Caller information
	 */
	private function throwBlockedUrlException($url, $pattern, $caller) {
		// Set HTTP status code to 500
		if (!headers_sent()) {
			http_response_code(500);
		}
		
		$errorMessage = "URL access blocked by security policy. ";
		$errorMessage .= "Pattern '$pattern' detected in URL. ";
		$errorMessage .= "Called from: " . $caller['file'] . " (Line: " . $caller['line'] . ")";
		
		throw new Exception($errorMessage, 500);
	}

	private function getCURL($url){
		//urls should begin with http ;-)
		$url=ltrim($url);
		$url=Str_replace(" ","+",$url); //to have no problems with image/png; mode=24bit!
		$url=str_replace(";","%3B",$url);
		if (func_num_args() == 2) {
			$auth = func_get_arg(1);
		} //auth should be an array of ['username', 'realm', 'password', 'auth_type'] - or false - problem would be, that these are stored without hashing them!
		$ch = curl_init ($url);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true); //for images
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		//hold cookies on redirects (302) - http://stackoverflow.com/questions/1458683/how-do-i-pass-cookies-on-a-curl-redirect - needed for print via internal owsproxy!
		curl_setopt($ch, CURLOPT_COOKIEFILE, "");
		//allow https connections and handle certificates quite simply ;-)
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeOut);
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->executionTimeOut);
		
		if ($this->curlSessionCookie !== false) {
			curl_setopt($ch,CURLOPT_COOKIE, $this->curlSessionCookie);
			//$e = new mb_exception("class_connector: cookie ".$this->curlSessionCookie);
		}
		//$e = new mb_notice("connector: test1:");
		//get hostname/ip out of url
		//$host = parse_url($url,PHP_URL_HOST);
		$arURL = parse_url($url);
		$host = $arURL["host"];
		$port = $arURL["port"];
		if($port == ''){
			$port = 80;
			if($arURL["scheme"] == "https"){
				$port = 443;
			}
		}

		$path = $arURL["path"];

		// fill array (HOSTs not for Proxy)
		$e = new mb_notice("class_connector.php: NOT_PROXY_HOSTS:".NOT_PROXY_HOSTS);
 	    	$NOT_PROXY_HOSTS_array = explode(",", NOT_PROXY_HOSTS);

 	    	//check if http_proxy is set as env, if yes, unset it for the curl action here, it will be reset somewhere below - normally not needed, cause it will be only available when at execution time of the script http://php.net/manual/en/function.putenv.php
		if (getenv('http_proxy')) {
			$e = new mb_notice("class_connector.php: current http_proxy: ".getenv('http_proxy')." will be unset by putenv('http_proxy')");
			$tmpHttpProxy = getenv('http_proxy');
			putenv("http_proxy"); //this should unset the variable???
		} else {
			$e = new mb_notice("class_connector.php: http_proxy is not set as env variable!");
			$tmpHttpProxy = getenv('http_proxy');
		}
 	    	//$tmpHttpProxy = getenv('http_proxy')?getenv('http_proxy') : "";
 	    	//putenv("http_proxy");
		//check if proxy is set and server not in NOT_PROXY_HOSTS
 	    	if(CONNECTION_PROXY != "" AND (in_array($host, $NOT_PROXY_HOSTS_array)!= true)){
			curl_setopt($ch, CURLOPT_PROXY,CONNECTION_PROXY.":".CONNECTION_PORT);
			$e = new mb_notice("class_connector.php: Proxy will be used!");
			if(CONNECTION_PASSWORD != ""){
				curl_setopt ($ch, CURLOPT_PROXYUSERPWD, CONNECTION_USER.':'.CONNECTION_PASSWORD);
			}
		} else {
			$e = new mb_notice("class_connector.php: Proxy will not be used!");
		}
		//TODO maybe allow basic authentication for client, but this will store the passwords in plain text
		//TODO: store the  passwords as digest hash. Therefor we have to handle the realm which is defined in the 401 header and return it back to the scripts like mod_loadwms.php to store the digest into the database - problem: curl cannot handle digest connection without clear username and password - we have to send our own headers
		if(isset($auth) && $auth != false) {
			curl_setopt($ch, CURLOPT_USERPWD, $auth['username'].':'.$auth['password']);
			if ($auth['auth_type'] == 'digest') {
				curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
			}
			if ($auth['auth_type'] == 'basic') {
				curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			}
		}



		//if httpType is POST, set CURLOPT_POST and CURLOPT_POSTFIELDS
		//and set a usefull http header
		if(strtoupper($this->httpType) == 'POST'){
			$headers = array(
					"POST ".$path." HTTP/1.1",
            			 	"Content-type: ".$this->httpContentType."; charset=".CHARSET,
           				"Cache-Control: no-cache",
	           		 	"Pragma: no-cache",
	           		 	"Content-length: ".strlen($this->httpPostData)
			);
			$e = new mb_notice("connector: CURL POST: ".$this->httpPostData);
			$e = new mb_notice("connector: CURL POST length: ".strlen($this->httpPostData));

			if ($this->curlSendCustomHeaders) {
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			}
			if ($this->httpPostFieldsNumber != 1){
				curl_setopt($ch,CURLOPT_POST,$this->httpPostFieldsNumber);
			} else {
				curl_setopt($ch, CURLOPT_POST, 1);
			}
			curl_setopt($ch, CURLOPT_POSTFIELDS, $this->httpPostData);
			//$e = new mb_exception($this->httpPostData);
		}
		$useragent=CONNECTION_USERAGENT;
		//Build own headers for GET Requests - maybe needful?
		if(strtoupper($this->httpType) == 'GET'){
			if ($this->externalHeaders !== "") {
				$headers = $this->externalHeaders;
			} else {
				$headers = array(
						"GET ".$path." HTTP/1.1",
						"User-Agent: ".$useragent,
           					//"Host: ".$host.":".$port,
	           		 		"Accept: */*",
						"Proxy-Connection: Keep-Alive"
				);
			}
		        if ($headers != 'empty') {
		            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			}
		}
		#curl_setopt($ch, CURLOPT_HEADER, true);
//$e = new mb_exception("class_connector.php: CURL connect to:".$url);
		//curl_setopt ($ch,CURLOPT_USERAGENT,$useragent);
		curl_setopt($ch,CURLOPT_DNS_USE_GLOBAL_CACHE, false);
		curl_setopt($ch,CURLINFO_HEADER_OUT, true);
		curl_setopt($ch, CURLOPT_FORBID_REUSE, false);
		//curl_setopt ($ch,HTTPPROXYTUNNEL, 1);
		//curl_setopt ($ch,CURLOPT_AUTOREFERER, 1);
		//curl_setopt ($ch,CURLOPT_VERBOSE, true);
		//$handle = fopen('/tmp/phpcurl_error.tmp', 'a'); //use this for debugging purposes
		//curl_setopt ($ch,CURLOPT_STDERR, $handle);
		$file = curl_exec ($ch);
		//handle http authentication
		$info = curl_getinfo($ch);
		/*$error_log = "";
		$error_log .= "http code: ".$info['http_code']."\n";
		$error_log .= "sent header: ".$info['request_header']."\n";
		$error_log .= "lookup time: ".$info['namelookup_time']."\n";
		$error_log .= "redirect_time: ".$info['redirect_time']."\n";
		$error_log .= "redirect_count: ".$info['redirect_count']."\n";*/
		$this->curlError = curl_error ($ch);
		if ($info['total_time'] == (float)0) {
			$this->timedOut = true;
			$e = new mb_exception("class_connector.php: Problem when connecting to external resource via curl - connection timed out: Waited more than ".$this->timeOut." seconds!");
		}
		if ($info['http_code'] == '401') {
			curl_close ($ch);
			return $info['http_code'];
		}
		if ($info['http_code'] == '502') {
			curl_close ($ch);
			$e = new mb_exception("class_connector.php: Problem with connecting Gateway - maybe problem with the configuration of the security proxy (mod_proxy?).");
			return $info['http_code'];
			/*fwrite($handle,"HEADER: \n");
			fwrite($handle,$error_log);
			fwrite($handle,"502: ".$file."\n");*/
		}
		$this->httpCode = $info['http_code'];
		curl_close ($ch);
		//fclose($handle);
		//reset the env variable http_proxy to the former value
		if ($tmpHttpProxy != '') {
			putenv("http_proxy=$tmpHttpProxy");
		}
//$e = new mb_exception("class_connector.php: CURL give back: ".$file);
		return $file;
	}

	public function getHttpCode() {
		return $this->httpCode;
	}

	private function getHTTP($url){
		if ($this->httpType == "get") {
			return @file_get_contents($url);
	 	}
		else {
			$errno = 0;
			$errstr = "";
			$urlComponentArray = parse_url($url);
			$scheme = $urlComponentArray["scheme"];
			$host = $urlComponentArray["host"];
			$port = $urlComponentArray["port"];
			if ($port == "") {
				if ($scheme == "https") {
					$port = 443;
				}
				else {
					$port = 80;
				}
			}
			$path = $urlComponentArray["path"];
			$query = $urlComponentArray["query"];
			$buf = '';
			if ($scheme == "https") {
				$fp = fsockopen("ssl://". $host, $port, $errno, $errstr);
			}
			else {
			    $fp = fsockopen($host, $port);
			}
			$postStr = "";
			$postPath = "POST " . $path . "?" . $query . " HTTP/".$this->httpVersion . "\r\n";
			$postStr .= $postPath;
		    fputs($fp, $postPath);

			$postHost = "Host: " . $host . "\r\n";
			$postStr .= $postHost;
		    fputs($fp, $postHost);

		    if ($this->isValidHttpContentType($this->httpContentType)) {
				$postContentType = "Content-type: " . $this->httpContentType . "\r\n";
				$postStr .= $postContentType;
		    	fputs($fp, $postContentType);
		    }
			$postContentLength = "Content-length: " . strlen($this->httpPostData) . "\r\n";
			$postStr .= $postContentLength;
		    fputs($fp, $postContentLength);

			$postClose = "Connection: close\r\n\r\n";
			$postStr .= $postClose;
		    fputs($fp, $postClose);

		    $postStr .= $this->httpPostData;
			fputs($fp, $this->httpPostData);

			//new mb_notice("connector.http.postData: ".$this->httpPostData);

		    $xmlstr = false;
		    //@TODO remove possibly infinite loop
			while (!feof($fp)) {
		    	$content = fgets($fp,4096);
//		    	if( strpos($content, '<?xml') === 0){
		    	if( strpos($content, '<') === 0){
		    		$xmlstr = true;
		    	}
		    	if($xmlstr == true){
		    		$buf .= $content;
		    	}
			}
		    fclose($fp);
//		    new mb_notice("connector.http.response: ".$buf);
		    return $buf;
		}
	}

	private function getSOCKET($url){
		$r = "";
		$fp = fsockopen (CONNECTION_PROXY, CONNECTION_PORT, $errno, $errstr, 30);
		if (!$fp) {
			echo "$errstr ($errno)<br />\n";
		}
		else {
			fputs ($fp, "GET ".$url." HTTP/1.0\r\n\r\n");
			while (!feof($fp)) {
				$r .= fgets($fp,4096);
			}
			fclose($fp);
			return $r;
		}
	}
}
?>
