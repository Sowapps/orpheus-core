<?php
/**
 * The core functions
 * 
 * PHP File containing all system functions.
 */

use \Exception as Exception;
use Orpheus\Config\Config;
use Orpheus\Core\ClassLoader;
use Orpheus\Exception\UserException;
use Orpheus\Exception\UserReportsException;
use Orpheus\Hook\Hook;

/**
 * Redirect the client to a destination by HTTP
 * 
 * @param $destination The destination to go. Default value is SCRIPT_NAME.
 * @see permanentRedirectTo()

 * Redirects the client to a $destination using HTTP headers.
 * Stops the running script.
 */
function redirectTo($destination=null) {
	if( !isset($destination) ) {
		$destination = $_SERVER['SCRIPT_NAME'];
	}
	header('Location: '.$destination);
	die();
}

/**
 * Redirect permanently the client to a destination by HTTP
 * 
 * @param $destination The destination to go. Default value is SCRIPT_NAME.
 * @see redirectTo()

 * Redirects permanently the client to a $destination using the HTTP headers.
 * The only difference with redirectTo() is the status code sent to the client.
 */
function permanentRedirectTo($destination=null) {
	header('HTTP/1.1 301 Moved Permanently', true, 301);
// 	header('HTTP/1.1 301 Moved Permanently', false, 301);
	redirectTo($destination);
}

/** 
 * Redirect the client to a destination by HTML
 * 
 * @param $destination The destination to go.
 * @param $time The time in seconds to wait before refresh.
 * @param $die True to stop the script.

 * Redirect the client to a $destination using the HTML meta tag.
 * Does not stop the running script, it only displays.
 */
function htmlRedirectTo($destination, $time=3, $die=0) {
	echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"{$time} ; URL={$destination}\">";
	if( $die ) {
		exit();
	}
}

/**
 * Do a binary test
 * 
 * @param int $value The value to compare.
 * @param int $reference The reference for the comparison.
 * @return True if $value is binary included in $reference.

 * Do a binary test, compare $value with $reference.
 * This function is very useful to do binary comparison for rights and inclusion in a value.
 */
function bintest($value, $reference) {
	return ( ($value & $reference) == $reference);
}

/**
 * Send a packaged response to the client.
 * 
 * @param $code string The response code.
 * @param $other array Other data to send to the client. Default value is an empty string.
 * @param $domain string The translation domain. Default value is 'global'.
 * @param $desc string The alternative description code. Default value is $code.

 * The response code is a status code, commonly a string.
 * User $Other to send arrays and objects to the client.
 * The packaged reponse is a json string that very useful for AJAX request.
 * This function stops the running script.
 */
function sendResponse($code, $other='', $domain='global', $desc=null) {
	if( !$domain ) {
		$domain	= 'global';
	}
	sendJSON(array(
		'code'			=> $code,
		'description'	=> t($desc ? $desc : $code, $domain),
		'other'			=> $other
	));
}

/**
 * Send a JSON response to the client.
 *
 * @param $data The data to send
 * 
 * Send data and end script
 */
function sendJSON($data) {
	header('Content-Type: application/json');
// 	header('Content-Type',	'application/json; charset=UTF-8');
// 	debug('sendJSON');
	die(json_encode($data));
}

// define('HTTP_OK',						200);
// define('HTTP_BAD_REQUEST',				400);
// define('HTTP_UNAUTHORIZED',				401);
// define('HTTP_FORBIDDEN',				403);
// define('HTTP_NOT_FOUND',				404);
// define('HTTP_INTERNAL_SERVER_ERROR',	500);
/**
 * Send a RESTful JSON response to the client.
 *
 * @param mixed $data The data to send
 * @param int $code
 * 
 * Send data and end script. This function takes care of exceptions and codes.
 */
function sendRESTfulJSON($data, $code=null) {
	/*
	if( $data instanceof RESTResponse ) {
		if( !$code ) {
			$code = $data->getCode();
		}
		$data = $data->getBody();
	}
	*/
	if( !$code ) {
		if( $data instanceof Exception ) {
			$code = ($data->getCode() < 100) ? $data->getCode() : HTTP_INTERNAL_SERVER_ERROR;
		} else {
			$code = HTTP_OK;
		}
	}
	http_response_code($code);
	if( $code !== HTTP_OK ) {
		// Formatted JSON Error
		if( $data instanceof Orpheus\Exception\UserReportsException ) {
			/* @var Orpheus\Exception\UserReportsException $data */
			sendResponse($data->getMessage(), $data->getReports(), $data->getDomain());
		} else
		if( $data instanceof UserException ) {
			sendResponse($data->getMessage(), '', $data->getDomain());
		} else
		if( $data instanceof Exception ) {
			sendResponse($data->getMessage());
		} else {
// 			if( is_string($data) ) {
// 			}
			sendResponse($data);
		}
	} else {
		// Pure JSON
		sendJSON($data);
	}
}

/**
 * Run a SSH2 command.
 * 
 * @param $command The command to execute.
 * @param $SSH2S Local settings for the connection.
 * @return The stream from ssh2_exec()

 * Runs a command on a SSH2 connection.
 * You can pass the connection settings array in argument but you can declare a global variable named $SSH2S too.
 */
function ssh2_run($command, $SSH2S=null) {
	if( !isset($SSH2S) ) {
		global $SSH2S;
	}
	$session = ssh2_connect($SSH2S['host']);
	if( $session === false ) {
		throw new Exception('SSH2_unableToConnect');
	}
	if( !ssh2_auth_password( $session , $SSH2S['users'] , $SSH2S['passwd'] ) ) {
		throw new Exception('SSH2_unableToIdentify');
	}
	$stream = ssh2_exec( $session, $command);
	if( $stream === false ) {
		throw new Exception('SSH2_execError');
	}
	return $stream;
}

/** 
 * Scans a directory cleanly.
 * @param string $dir The path to the directory to scan.
 * @param boolean $sorting_order True to reverse results order. Default value is False.
 * @return string[] An array of the files in this directory.

 * Scans a directory and returns a clean result.
 */
function cleanscandir($dir, $sorting_order=false) {
	try {
		$result = scandir($dir);
	} catch(Exception $e) {
		return array();
	}
	unset($result[0]);
	unset($result[1]);
	if( $sorting_order ) {
		rsort($result);
	}
	return $result;
}

/**
 * Stringify any variable
 * 
 * @param mixed $s the input data to stringify
 * @return string
 */
function stringify($s) {
	if( is_object($s) && $s instanceof Exception ) {
		$s = formatException($s);
	} else {
		$s = "\n".print_r($s, 1);
	}
	return $s;
}

/**
 * Convert a variable a HTML-readable string
 *
 * @param mixed $s the input data to stringify
 * @return string
 * @see toString()
 */
function toHtml($s) {
	if( $s===NULL ) {
		$s = '{NULL}';
	} else if( $s === false ) {
		$s = '{FALSE}';
	} else if( $s === true ) {
		$s = '{TRUE}';
	} else if( !is_scalar($s) ) {
		$s = '<pre>'.print_r($s, 1).'</pre>';
	}
	return $s;
}

/**
 * Convert a variable a Text-readable string
 *
 * @param mixed $s the input data to stringify
 * @return string
 * @see toHtml()
 */
function toString($s) {
	if( $s===NULL ) {
		$s = 'NULL';
	} else if( $s === false ) {
		$s = 'FALSE';
	} else if( $s === true ) {
		$s = 'TRUE';
	} else if( is_array($s) || is_object($s) ) {
		$s	= json_encode($s);
// 		if( is_object($s) ) {
// 			$s	= (array) $s;
// 			$assoc = true;
// 		} else {
// 			$assoc = !isset($s[0]);
// 		}
// 		if( $assoc ) {
// 			$t	= '';
// 			foreach( $s as $key => $value ) {
// 				$t	.= ($t ? ', ' : '').
// 			}
// 			$s	= '{'.$s.'}';
// 		} else {
// 			$s	= '['.implode(', ', $s).']';
// 		}
	} else if( !is_scalar($s) ) {
		$s = print_r($s, 1);
	}
	return $s;
}

/**
 * Format the input Exception to a human-readable string
 * 
 * @param Exception $e
 * @return string
 */
function formatException($e) {
	return 'Exception \''.get_class($e).'\' with '.( $e->getMessage() ? " message '{$e->getMessage()}'" : 'no message')
		.' in '.$e->getFile().':'.$e->getLine()."\n<pre>".$e->getTraceAsString().'</pre>';
}

/**
 * Get the debug trace filtered by $filterStartWith 
 * 
 * @param string $filterStartWith Exclude functions starting with this value
 * @return array The filtered backtrace
 */
function getDebugTrace($filterStartWith=null) {
	$backtrace = debug_backtrace();
	unset($backtrace[0]);
	if( $filterStartWith !== null ) {
		$prev = null;
		foreach( $backtrace as $i => $trace ) {
			if( stripos($trace['function'], $filterStartWith) === 0 ) {
				if( $prev !== null ) {
					unset($backtrace[$prev]);
				}
				$prev = $i;
		
			} else {
				break;
			}
		}
	}
	return array_values($backtrace);
}

/**
 * Log a report in a file
 * 
 * @param $report The report to log.
 * @param $file The log file path.
 * @param $action The action associated to the report. Default value is an empty string.
 * @param $message The message to display. Default is an empty string. See description for details.
 * @warning This function require a writable log file.

 * Log an error in a file serializing data to JSON.
 * Each line of the file is a JSON string of the reports.
 * The log folder is the constant LOGSPATH.
 * Take care of this behavior:
 *	If message is NULL, it won't display any report
 *	Else if DEV_VERSION, displays report
 *	Else if message is empty, throw exception
 *	Else it displays the message.
 */
function log_report($report, $file, $action='', $message='') {
	if( !is_scalar($report) ) {
		if( $report instanceof Exception ) {
			$exception = $report;
			$report	= $exception->getMessage();
		} else {
			$report	= stringify($report);
		}
// 		$report	= 'NON-SCALAR::'.stringify($report);//."\n".print_r($report, 1);
	}
	$error = array(
		'id' => uniqid('OL', true),
		'date' => date('c'),
		'report' => $report,
		'action' => $action,
		'trace' => isset($exception) ? $exception->getTrace() : getDebugTrace('log'),
		'crc32' => crc32(isset($exception) ? formatException($exception) : $report).''
	);
	$logFilePath = LOGSPATH.$file;
	try {
		file_put_contents($logFilePath, json_encode($error)."\n", FILE_APPEND);
	} catch( Exception $e ) {
		$error['report'] .= "<br />\n<b>And we met an error logging this report:</b><br />\n".stringify($e);
	}
	if( DEV_VERSION ) {
// 	if( DEV_VERSION && isset($exception) ) {
		if( !isset($exception) ) {
			$exception = new Exception($report);
		}
		displayException($exception, $action);
		die();
	}
	if( $message !== NULL ) {// Yeh != NULL, not !empty, null cause no report to user
// 		if( DEV_VERSION ) {
// 			displayException($exception, $action);
// 			$error['message']	= $message;
// 			$error['page']		= nl2br(htmlentities($GLOBALS['Page']));
// 			// Display a pretty formatted error report
// 			global $RENDERING;
// 			if( !class_exists($RENDERING) || !$RENDERING::doDisplay('error', $error) ) {
// 				// If we fail in our display of this error, this is fatal.
// 				echo print_r($error, 1);
// 			}
// 		} else
		if( empty($message) ) {
			throw new Exception('fatalErrorOccurred');
			
		} else {
			die($message);
		}
	}
}

/**
 * Log a debug
 * 
 * @param $report The debug report to log.
 * @param $action The action associated to the report. Default value is an empty string.
 * @see log_report()

 * Log a debug. The log file is the constant LOGFILE_DEBUG.
 */
function log_debug($report, $action='') {
	log_report($report, LOGFILE_DEBUG, $action, null);
}

/**
 * Log a hack attemp
 * 
 * @param $report The report to log.
 * @param $action The action associated to the report. Default value is an empty string.
 * @param $message If False, it won't display the report, else if a not empty string, it displays it, else it takes the report's value.
 * @see log_report()

 * Logs a hack attemp.
 * The log file is the constant HACKFILENAME or, if undefined, '.hack'.
 */
function log_hack($report, $action='', $message=null) {
	global $USER;
	log_report($report.' 
[ IP: '.$_SERVER['REMOTE_ADDR'].'; User: '.(isset($USER) ? "$USER #".$USER->id() : 'N/A').'; agent: '.(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'N/A').'; referer: '.(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'N/A').' ]',
		LOGFILE_HACK, $action, $message);
}

/**
 * Log a system error
 * 
 * @param string $report The report to log.
 * @param string $action The action associated to the report. Default value is an empty string.
 * @param boolean $fatal True if the error is fatal, it stops script. Default value is true.
 * @see log_report()

 * Log a system error
 * The log file is the constant SYSLOGFILENAME or, if undefined, '.log_error'.
 */
function log_error($report, $action='', $fatal=true) {
	log_report($report, LOGFILE_SYSTEM, $action,
// 		!$fatal && !DEV_VERSION ? null :
		!$fatal ? null :
			(is_string($fatal) ? $fatal : "A fatal error occurred, retry later.<br />\nUne erreur fatale est survenue, veuillez re-essayer plus tard.").
			(DEV_VERSION ? '<br /><pre>'.print_r(debug_backtrace(), 1).'</pre>' : ''));
}

/**
 * Log a sql error
 * 
 * @param $report The report to log.
 * @param $action The action associated to the report. Default value is an empty string.
 * @see log_report()

 * Log a sql error
 * The log file is the constant PDOLOGFILENAME or, if undefined, '.pdo_error'.
 */
function sql_error($report, $action='') {
	log_report($report, LOGFILE_SQL, $action, null);// NULL to do nothing
}

/**
 * Escape quotes from a string
 *
 * @param $str The string to escape.
 * @param $flags The flags option.
 * @return The escaped string.

 * Escape the text $str from quotes using smart flags.
 */
function escapeQuotes($str, $flags=ESCAPE_ALLQUOTES) {
	if( !$flags ) {
		$flags	= ESCAPE_ALLQUOTES;
	}
	$in		= array();
	$out	= array();
	$toHTML	= bintest($flags, ESCAPE_TOHTML);
	if( bintest($flags, ESCAPE_SIMPLEQUOTES) ) {
		$in[]	= "'";
		$out[]	= "\\'";
	}
	if( bintest($flags, ESCAPE_DOUBLEQUOTES) ) {
		$in[]	= '"';
		$out[]	= $toHTML ? '&quot;' : '\\"';
	}
	return str_replace($in, $out, $str);
}
define('ESCAPE_SIMPLEQUOTES', 1<<1);
define('ESCAPE_DOUBLEQUOTES', 1<<2);
define('ESCAPE_ALLQUOTES', ESCAPE_SIMPLEQUOTES|ESCAPE_DOUBLEQUOTES);
define('ESCAPE_TOHTML', 1<<3);
define('ESCAPE_ALLQUOTES_TOHTML', ESCAPE_ALLQUOTES|ESCAPE_TOHTML);
define('ESCAPE_DOUBLEQUOTES_TOHTML', ESCAPE_DOUBLEQUOTES|ESCAPE_TOHTML);

/**
 * Display text as HTML
 * 
 * @param $text The string to display
 */
function displayText($text) {
	echo text2HTML($text);
}

/**
 * Convert Text to HTML
 *
 * @param $text The string to convert
 */
function text2HTML($text) {
	return nl2br(escapeText($text));
}

/**
 * Format a string to be a html attribute value
 * 
 * @param mixed $var The variable to format
 * @return The escaped string
 * 
 * Escape the text $str from special characters for HTML Attribute usage
 */
function htmlFormATtr($var) {
	if( !is_scalar($var) ) {
		$var = json_encode($var);
	}
	$flags = ENT_QUOTES | ENT_IGNORE;
	if( defined('ENT_HTML5') ) {
		$flags |= ENT_HTML5;
	}
	return htmlentities($var, $flags, 'UTF-8', false);
}

/**
 * Encode to an internal URL
 * 
 * @param $u The URL to encode.
 * @return The encoded URL
 * 
 * Encode to URL and secures some more special characters
 */
function iURLEncode($u) {
	return str_replace(array(".", '%2F'), array(":46", ''), urlencode($u));
}

/**
 * Decode from an internal URL
 * 
 * @param $u The URL to decode.
 * @return The decoded URL
 * 
 * Decode from URL
 */
function iURLDecode($u) {
	return urldecode(str_replace(":46", ".", $u));
}

/**
 * Parse Fields array to string
 * 
 * @param $fields The fields array
 * @param $quote The quote to escape key
 * @return A string as fields list
 * 
 * It parses a field array to a fields list for queries
 */
function parseFields(array $fields, $quote='"') {
	$list = '';
	foreach($fields as $key => $value) {
		$list .= (!empty($list) ? ', ' : '').$quote.$key.$quote.'='.$value;
	}
	return $list;
}

/**
 * Get value from an Array Path
 * 
 * @param array $array The array to get the value from.
 * @param string $apath The path used to browse the array.
 * @param mixed $default The default value returned if array is valid but key is not found.
 * @param boolean $pathRequired True if the path is required. Default value is False.
 * @return mixed The value from $apath in $array.
 * @see build_apath()
 *
 * Get value from an Array Path using / as separator.
 * Return null if parameters are invalids, $default if the path is not found else the value.
 * If $default is not null and returned value is null, you can infer your parameters are invalids.
 */
function apath_get($array, $apath, $default=null, $pathRequired=false) {
	if( empty($array) || !is_array($array) || $apath === NULL ) {
		return $default;
		// Was not returning default value if array was empty
// 		return null;
	}
	list($key, $suffix)	= explodeList('/', $apath, 2);
	// If element does not exist in array
	if( !isset($array[$key]) ) {
		// If has a child, the child could not be found
		// Else container exists, but element not found.
		return ($pathRequired && $suffix !== NULL) ? null : $default;
	}
	return $suffix !== NULL ? apath_get($array[$key], $suffix) : $array[$key];
}

/**
 * Set value into an Array Path
 *
 * @param array $array The array to get the value from.
 * @param string $apath The path used to browse the array.
 * @param mixed $value The value to set in array
 * @param boolean $overwrite True to overwrite existing value. Default value is True.
 * @see apath_get()
 *
 * Set value into array using an Array Path with / as separator.
 */
function apath_setp(&$array, $apath, $value, $overwrite=true) {
	if( $array === NULL ) {
		$array	= array();
	}
// 	debug("Set array $apath to value $value", $array);
// 	if( empty($array) || !is_array($array) || $apath === NULL ) {
// 		return null;
// 	}
	
	list($key, $suffix)	= explodeList('/', $apath, 2);//('/', $apath, 2);
	// The path ends here
	if( $suffix === NULL ) {
		// NULL value will always be overwritten
		if( $overwrite === true || !isset($array[$key]) ) {
			$array[$key] = $value;
		}
		return;
	}
	// The path continues
	if( !isset($array[$key]) ) {
		$array[$key]	= array();
	}
	apath_setp($array[$key], $suffix, $value, $overwrite);
}

/**
 * Build all path to browse array
 * 
 * @param $array The array to get the value from.
 * @param $prefix The prefix to get the value, this is for an internal use only.
 * @return An array of apath to get all values.
 * @see apath_get()
 *
 * Builds an array associating all values with their apath of the given one using / as separator.
 * e.g Array('path'=>array('to'=>array('value'=>'value'))) => Array('path/to/value'=>'value')
 */
function build_apath($array, $prefix='') {
	if( empty($array) || !is_array($array) ) {
		return array();
	}
	$r = array();
	foreach($array as $key => $value) {
		if( is_array($value) ) {
			$r += build_apath($value, $prefix.$key.'/');
		} else {
			$r[$prefix.$key] = $value; 
		}
	}
	return $r;
}

/** 
 * Imports the required class(es).
 * @param $pkgPath The package path.
 * @warning You should only use lowercase for package names.
 * @deprecated use namespaces, packages and autoload system
 * 
 * Include a class from a package in the libs directory, or calls the package loader.
 * e.g: "package.myclass", "package.other.*", "package"
 * 
 * Packages should include a _loader.php or loader.php file (it is detected in that order).
 * Class files should be named classname_class.php
 */
function using($pkgPath) {
	$pkgPath	= LIBSDIR.str_replace('.', '/', $pkgPath);
	$lowerPath	= strtolower($pkgPath);
	// Including all contents of a package
	if( substr($lowerPath, -2) == '.*' ) {
		$dir = pathOf(substr($lowerPath, 0, -2));
		$files = scandir($dir);
		foreach($files as $file) {
			if( preg_match("#^[^\.].*_class.php$#", $file) ) {
				require_once $dir.'/'.$file;
			}
		}
		return;
	}
	// Including loader of a package
	$path = null;
	if( existsPathOf($lowerPath, $path) && is_dir($path) ) {
		if( file_exists($path.'/_loader.php') ) {
			require_once $path.'/_loader.php';
// 		} else {
// 			require_once $path.'/loader.php';
		}
		return;
	}
	// Including a class
	require_once existsPathOf($lowerPath.'_class.php', $path) ? $path : pathOf($pkgPath.'.php');
}

/** 
 * Add a class to the autoload.
 * @param string $className The class name
 * @param string $classPath The class path
 * 
 * Add the class to the autoload list, associated with its file.
 * The semi relative path syntax has priority over the full relative path syntax.
 * e.g: ("MyClass", "mylib/myClass") => libs/mylib/myClass_class.php
 * or ("MyClass2", "mylib/myClass2.php") => libs/mylib/myClass.php
 */
function addAutoload($className, $classPath) {
	ClassLoader::get()->setClass($className, $classPath);
}
/*
function addAutoload($className, $classPath) {
	global $AUTOLOADS;
	$className = strtolower($className);
	if( !empty($AUTOLOADS[$className]) ) {
		return false;
	}
	if(
		// Pure object naming
		existsPathOf(LIBSDIR.$classPath.'.php', $path) ||
		// Old Orpheus naming
		existsPathOf(LIBSDIR.$classPath.'_class.php', $path) ||
		// Full path
		existsPathOf(LIBSDIR.$classPath, $path)
	) {
		$AUTOLOADS[$className] = $path;
// 		$AUTOLOADS[$className] = $classPath.'.php';
		
// 	} else
// 	if(  ) {
// 		// Old Orpheus naming
// 		$AUTOLOADS[$className] = $classPath.'_class.php';
		
// 	} else
// 	if( existsPathOf(LIBSDIR.$classPath) ) {
// 		// Full naming
// 		$AUTOLOADS[$className] = $classPath;
		
	} else {
		throw new Exception("Class file of \"{$className}\" not found.");
	}
	return true;
}
 */

/** 
 * Starts a new report stream
 * @param string $stream The new report stream name
 * @see endReportStream()

 * A new report stream starts, all new reports will be added to this stream.
 */
function startReportStream($stream) {
// 	global $REPORT_STREAM;
// 	$REPORT_STREAM = $stream;
	$GLOBALS['REPORT_STREAM'] = $stream;
}

/** 
 * Ends the current stream
 * @see startReportStream()
 * Ends the current stream by setting current stream to the global one, so you can not end global stream.
 */
function endReportStream() {
	startReportStream('global');
}
endReportStream();

/** 
 * Transfers the stream reports to another
 * @param $from Transfers $from this stream. Default value is null (current stream).
 * @param $to Transfers $to this stream. Default value is global.
 * 
 * Transfers the stream reports to another
 */
function transferReportStream($from=null, $to='global') {
	if( is_null($from) ) {
		$from = $GLOBALS['REPORT_STREAM'];
	}
	if( $from==$to ) { return false; }
	global $REPORTS;
	if( !empty($REPORTS[$from]) ) {
		if( !isset($REPORTS[$to]) ) {
			$REPORTS[$to] = array();
		}
		$REPORTS[$to] = isset($REPORTS[$to]) ? array_merge_recursive($REPORTS[$to], $REPORTS[$from]) : $REPORTS[$from];
		unset($REPORTS[$from]);
	}
	return true;
}

/** 
 * Adds a report
 * @param $report string The report (Commonly a string or an UserException).
 * @param $type string The type of the message.
 * @param $domain string The domain to use to automatically translate the message. Default value is 'global'.
 * @param string $code The code to use for this report. Default is $report.
 * @param integer $severity The severity of report. Default value is 0.
 * @return boolean False if rejected.
 * @see reportSuccess(), reportError()

 * Adds the report $message to the list of reports for this $type.
 * The type of the message is commonly 'success' or 'error'.
 */
function addReport($report, $type, $domain='global', $code=null, $severity=0) {
// 	debug("Add report($report, $type, $domain, $code, $severity)");
	global $REPORTS, $REPORT_STREAM, $REJREPORTS, $DISABLE_REPORT;
	if( !empty($DISABLE_REPORT) ) { return false; }
	if( !$domain ) {
		$domain = 'global';
	}
	$report = "$report";
	if( !$code ) {
		$code	= $report;
	}
	if( isset($REJREPORTS[$report]) && (empty($REJREPORTS[$report]['type']) || in_array($type, $REJREPORTS[$report]['type'])) ) {
		return false;
	}
	if( !isset($REPORTS[$REPORT_STREAM]) ) {
		$REPORTS[$REPORT_STREAM] = array();
	}
	if( !isset($REPORTS[$REPORT_STREAM][$type]) ) {
		$REPORTS[$REPORT_STREAM][$type] = array();
	}
	$report	= t($report, $domain);// Added recently, require tests
	$REPORTS[$REPORT_STREAM][$type][] = array('code'=>$code, 'report'=>$report, 'domain'=>$domain, 'severity'=>$severity);
// 	$REPORTS[$REPORT_STREAM][$type][] = array('c'=>$report, 'r'=>t($report, $domain), 'd'=>$domain);
	return true;
}

/** 
 * Reports a success
 * @param $report string The message to report.
 * @param $domain string The domain fo the message. Not used for translation. Default value is global.
 * @see addReport()

 * Adds the report $message to the list of reports for this type 'success'.
 */
function reportSuccess($report, $domain=null) {
	return addReport($report, 'success', $domain);
}

/** 
 * Reports an information to the user
 * @param string $report The message to report.
 * @param string $domain The domain fo the message. Not used for translation. Default value is global.
 * @see addReport()

 * Adds the report $message to the list of reports for this type 'info'.
 */
function reportInfo($report, $domain=null) {
	return addReport($report, 'info', $domain);
}

/** 
 * Reports a warning
 * @param string $report The message to report.
 * @param string $domain The domain fo the message. Not used for translation. Default value is the domain of Exception in case of UserException else 'global'.
 * @see addReport()

 * Adds the report $message to the list of reports for this type 'warning'.
 * Warning come in some special cases, we meet it when we do automatic checks before loading contents and there is something to report to the user.
 */
function reportWarning($report, $domain=null) {
	return reportError($report, $domain, 0);
// 	return addReport($report, 'warning', $domain);
}

/** 
 * Reports an error
 * @param string $report The report.
 * @param string $domain The domain fo the message. Default value is the domain of Exception in case of UserException else 'global'.
 * @param integer $severity The severity of the error, commonly 1 for standard user error and 0 for warning. Default value is 1.
 * @see addReport()

 * Adds the report $message to the list of reports for this type 'error'.
 */
function reportError($report, $domain=null, $severity=1) {
	$code	= null;
	if( $report instanceof UserException ) {
// 		if( class_exists('InvalidFieldException') && $report instanceof InvalidFieldException ) {
// 		if( $report instanceof InvalidFieldException ) {
// 			// InvalidFieldException translates the message when using __toString method, so we need to get the original code
// 			// Should be improved by object inheritance
// 			$code	= $report->getMessage();
// 		}
		$code = $report->getMessage();
		if( $domain === NULL ) {
			$domain = $report->getDomain();
		}
	}
	return addReport($report, 'error', $domain === NULL ? 'global' : $domain, $code, $severity);
}

/**
 * Check if there is error reports
 * 
 * @return boolean True if there is any error report.
 */
function hasErrorReports() {
	global $REPORTS;
	if( empty($REPORTS) ) { return false; }
// 	foreach($REPORTS as $stream => $types) {
	foreach($REPORTS as $types) {
		if( !empty($types['error']) ) {
			return true;
		}
	}
	return false;
}

/**
 * Reject reports
 * 
 * @param mixed $report The report message to reject, could be an array.
 * @param string $type Filter reject by type, could be an array. Default value is null, not filtering.
 * @see addReport()
 * 
 * Register this report to be rejected in the future, addReport() will check it.
 * All previous values for this report will be replaced.
 */
function rejectReport($report, $type=null) {
	global $REJREPORTS;
	if( !isset($REJREPORTS) ) { $REJREPORTS = array(); }
	if( !is_array($report) ) {
		$report = array($report);
	}
	$d = array();
	if( isset($type) ) {
		$d['type'] = is_array($type) ? $type : array($type);
	}
	foreach( $report as $r ) {
		$d['report'] = $r;
		$REJREPORTS["$r"] = $d;
	}
}

/**
 * Get some/all reports
 * 
 * @param string $stream The stream to get the reports. Default value is "global".
 * @param string $type Filter results by report type. Default value is null.
 * @param boolean $delete True to delete entries from the list. Default value is true.
 * @see getReportsHTML()

 * Get all reports from the list of $domain optionnally filtered by type.
 */
function getReports($stream='global', $type=null, $delete=1) {
	global $REPORTS;
	if( empty($REPORTS[$stream]) ) { return array(); }
	// Type specified
	if( !empty($type) ) {
		if( empty($REPORTS[$stream][$type]) ) { return array(); }
		$r = $REPORTS[$stream][$type];
		if( $delete ) {
			unset($REPORTS[$stream][$type]);
		}
		return array($type=>$r);
	}
	// All types
	$r = $REPORTS[$stream];
	if( $delete ) {
		$REPORTS[$stream] = array();
	}
	return $r;
}

/** 
 * Get some/all reports as flatten array
 * 
 * @param string $stream The stream to get the reports. Default value is "global".
 * @param string $type Filter results by report type. Default value is null.
 * @param boolean $delete True to delete entries from the list. Default value is true.
 * @return array[].
 * @see getReports()

 * Get all reports from the list of $domain optionnally filtered by type.
 */
function getFlatReports($stream='global', $type=null, $delete=1) {
	$reports	= array();
	foreach( getReports($stream, $type, $delete) as $rType => $rTypeReports ) {
		foreach( $rTypeReports as $report ) {
			$report['type']	= $rType;
			$reports[]		= $report;
		}
	}
	return $reports;
}

/** 
 * Get some/all reports as HTML
 * 
 * @param	$stream string The stream to get the reports. Default value is 'global'.
 * @param	$rejected array An array of rejected messages. Default value is an empty array.
 * @param	$delete boolean True to delete entries from the list. Default value is true.
 * @return	The renderer HTML.
 * @see displayReportsHTML()
 * @see getHTMLReport()

 * Get all reports from the list of $domain and generates the HTML source to display.
 */
function getReportsHTML($stream='global', $rejected=array(), $delete=true) {
	$reports = getReports($stream, null, $delete);
	if( empty($reports) ) { return ''; }
	$reportHTML = '';
	foreach( $reports as $type => &$rl ) {
		foreach( $rl as $report) {
			$msg = "{$report['report']}";
			if( !in_array($msg, $rejected) ) {
				$reportHTML .= getHTMLReport($stream, $msg, $report['domain'], $type);
			}
		}
	}
	return $reportHTML;
}

/** 
 * Get one report as HTML
 * 
 * @param string $stream	The stream of the report.
 * @param string $report	The message to report.
 * @param string $domain	The domain of the report.
 * @param string $type		The type of the report.

 * Return a valid HTML report.
 * This function is only a HTML generator.
 */
function getHTMLReport($stream, $report, $domain, $type) {
// 	if( class_exists('HTMLRendering', true) ) {
// 		return HTMLRendering::renderReport($report, $domain, $type, $stream);
// 	} 
	return '
		<div class="report report_'.$stream.' '.$type.' '.$domain.'">'.nl2br($report).'</div>';
}

/** 
 * Display reports as HTML
 * 
 * @param string $stream The stream to display. Default value is 'global'.
 * @param string[] $rejected An array of rejected messages. Can be the first parameter.
 * @param boolean $delete True to delete entries from the list.
 * @see getReportsHTML()

 * Displays all reports from the list of $domain and displays generated HTML source.
 */
function displayReportsHTML($stream='global', $rejected=array(), $delete=1) {
	if( is_array($stream) && empty($rejected) ) {
		$rejected	= $stream;
		$stream		= 'global';
	}
	echo '
	<div class="reports '.$stream.'">
	'.getReportsHTML($stream, $rejected, $delete).'
	</div>';
}

/** 
 * Get POST data
 * 
 * @param string $path The path to retrieve. The default value is null (retrieves all data).
 * @return mixed Data using the path or all data from POST array.
 * @see isPOST()
 * @see extractFrom()

 * Get data from a POST request using the $path.
 * With no parameter or parameter null, all data are returned.
 */
function POST($path=null) {
	return extractFrom($path, $_POST);
}

/** 
 * Check an existing post key
 * 
 * @param $path string The path to the array. The default value is null (search in POST).
 * @param $value int The output value of the item to delete.
 * @return True if there is an item to delete

 * This function is used to key the key value from an array sent by post
 * E.g You use POST to delete an item from a list, it's name is delete[ID], where ID is the ID of this item
 * If you call hasPOSTKey("delete", $itemID), the function will return true if a delete item is defined and $itemID will contain the ID of the item to delete.
 */
function hasPOSTKey($path=null, &$value=null) {
	$v = POST($path);
	if( !$v || !is_array($v) ) { return false; }
	$value	= key($v);
	return true;
}

/** 
 * Get GET data
 * 
 * @param $path The path to retrieve. The default value is null (retrieves all data).
 * @return Data using the path or all data from GET array.
 * @see isGET()
 * @see extractFrom()

 * Get data from a GET request using the $path.
 * With no parameter or parameter null, all data are returned.
 */
function GET($path=null) {
	return extractFrom($path, $_GET);
}

/** 
 * Check the POST status
 * 
 * @param $apath The apath to test.
 * @return True if the request is a POST one. Compares also the $key if not null.
 * @see POST()
 * 
 * Check the POST status to retrieve data from a form.
 * You can specify the name of your submit button as first parameter.
 * We advise to use the name of your submit button, but you can also use another important field of your form.
 */
function isPOST($apath=null) {
	// !empty because $_POST is always set in case of web access, but is an empty array
	return !empty($_POST) && ($apath===NULL || POST($apath)!==NULL);
}

/** 
 * Check the GET status
 * 
 * @param $apath The apath to test.
 * @return True if the request is a GET one. Compares also the $key if not null.
 * @see GET()
 * 
 * Check the GET status to retrieve data from a form.
 * You can specify the name of your submit button as first parameter.
 * We advise to use the name of your submit button, but you can also use another important field of your form.
 */
function isGET($apath=null) {
	// !empty because $_GET is always set in case of web access, but is an empty array
	return !empty($_GET) && ($apath===NULL || GET($apath)!==NULL);
}

/** 
 * Extract data from array using apath
 * 
 * @param $apath The apath to retrieve. null retrieves all data.
 * @param $array The array of data to browse.
 * @return Data using the apath or all data from the given array.

 * Get data from an array using the $apath.
 * If $apath is null, all data are returned.
 */
function extractFrom($apath, $array) {
	return $apath===NULL ? $array : apath_get($array, $apath);
// 	return is_null($path) ? $array : ( (!is_null($v = apath_get($array, $path))) ? $v : false) ;
}

/**
 * Get the HTML value
 * 
 * @param $name The name of the field
 * @param $data The array of data where to look for. Default value is $formData (if exist) or $_POST
 * @param $default The default value if $name is not defined in $data
 * @return A HTML source with the "value" attribute.
 *
 * Get the HTML value attribut from an array of data if this $name exists.
 */
function htmlValue($name, $data=null, $default='') {
	fillFormData($data);
	$v = apath_get($data, $name, $default);
	return !empty($v) ? " value=\"{$v}\"" : '';
}

/**
 * Generate the HTML source for a select tag
 * 
 * @param string $name The name of the field.
 * @param array $values The values to build the dropdown menu.
 * @param array $data The array of data where to look for. Default value is $formData (if exist) or $_POST
 * @param string $selected The selected value from the data. Default value is null (no selection).
 * @param string $prefix The prefix to use for the text name of values. Default value is an empty string.
 * @param string $domain The domain to apply the Key. Default value is 'global'.
 * @param string $tagAttr Additional attributes for the SELECT tag.
 * @return string A HTML source for the built SELECT tag.
 * @see htmlOptions
 * @warning This function is under conflict with name attribute and last form data values, prefer htmlOptions()
 *
 * Generate the HTML source for a select tag from the $data.
 */
function htmlSelect($name, $values, $data=null, $selected=null, $prefix='', $domain='global', $tagAttr='') {
	fillFormData($data);
	$namePath = explode('/', $name);
	$name = $namePath[count($namePath)-1];
	$htmlName = '';
	foreach( $namePath as $index => $path ) {
		$htmlName .= ( $index ) ? "[{$path}]" : $path;
	}
	$tagAttr .= ' name="'.$htmlName.'"';
	$v = apath_get($data, $name);
	if( !empty($v) ) {//is_null($selected) && 
		$selected = $v;
	}
	$opts = '';
	foreach( $values as $dataKey => $dataValue ) {
		$addAttr = '';
		if( is_array($dataValue) ) {
			list($dataValue, $addAttr) = array_pad($dataValue, 2, null);
		}
		$key = is_int($dataKey) ? $dataValue : $dataKey;// If this is an associative array, we use the key, else the value.
		$opts .= '
	<option value="'.$dataValue.'" '.( ($dataValue == $selected) ? 'selected="selected"' : '').' '.$addAttr.'>'.t($prefix.$key, $domain).'</option>';
	}
	return "
	<select {$tagAttr}>{$opts}
	</select>";
}

/**
 * Generate the HTML source for options of a select tag
 * 
 * @param string $fieldPath The name path to the field.
 * @param array $values The values to build the dropdown menu.
 * @param string $default The default selected value. Default value is null (no selection).
 * @param integer $matches Define the associativity between array and option values. Default value is OPT_VALUE2LABEL (as null).
 * @param string $prefix The prefix to use for the text name of values. Default value is an empty string.
 * @param string $domain The domain to apply the Key. Default value is 'global'.
 * @return string A HTML source for the built SELECT tag.
 * @see htmlOption()
 *
 * Generate the HTML source for a SELECT from the $data.
 * For associative arrays, we commonly use the value=>label model (OPT_VALUE2LABEL) but sometimes for associative arrays we could prefer the label=>value model (OPT_LABEL2VALUE).
 * You can use your own combination with defined constants OPT_VALUE_IS_VALUE, OPT_VALUE_IS_KEY, OPT_LABEL_IS_VALUE and OPT_LABEL_IS_KEY.
 * Common combinations are OPT_LABEL2VALUE, OPT_VALUE2LABEL and OPT_VALUE.
 * The label is prefixed with $prefix and translated using t(). This function allows bidimensional arrays in $values, used as option group.
 */
function htmlOptions($fieldPath, $values, $default=null, $matches=null, $prefix='', $domain='global') {
	if( $matches===NULL ) { $matches = OPT_VALUE2LABEL; }
	// Value of selected/default option
	$selValue = null;
	fillInputValue($selValue, $fieldPath, OPT_PERMANENTOBJECT && is_object($default) ? $default->id() : $default);
	$opts	= '';
	foreach( $values as $dataKey => $elValue ) {
		if( $elValue===null ) { continue; }
		if( is_array($elValue) ) {
			$opts .= '<optgroup label="'.t($prefix.$dataKey, $domain).'">'.htmlOptions($fieldPath, $elValue, $default, $matches, $prefix, $domain).'</optgroup>';
			continue;
		}
		$addAttr	= '';
		if( is_array($elValue) ) {
			list($elValue, $addAttr) = array_pad($elValue, 2, null);
		}
		if( bintest($matches, OPT_PERMANENTOBJECT) ) {
			$optLabel	= "$elValue";
			$optValue	= $elValue->id();
		} else {
			$optLabel	= bintest($matches, OPT_LABEL_IS_KEY) ? $dataKey : $elValue;
			$optValue	= bintest($matches, OPT_VALUE_IS_KEY) ? $dataKey : $elValue;
		}
		$opts .= htmlOption($optValue, t($prefix.$optLabel, $domain), is_array($selValue) ? in_array("$optValue", $selValue) : "$selValue"==="$optValue", $addAttr);
	}
	return $opts;
}

/**
 * Display htmlOptions()
 * 
 * @param string $fieldPath The name path to the field.
 * @param array $values The values to build the dropdown menu.
 * @param string $default The default selected value. Default value is null (no selection).
 * @param integer $matches Define the associativity between array and option values. Default value is OPT_VALUE2LABEL (as null).
 * @param string $prefix The prefix to use for the text name of values. Default value is an empty string.
 * @param string $domain The domain to apply the Key. Default value is 'global'.
 * @see htmlOptions()
 */
function _htmlOptions($fieldPath, $values, $default=null, $matches=null, $prefix='', $domain='global') {
	echo htmlOptions($fieldPath, $values, $default, $matches, $prefix, $domain);
}
define('OPT_VALUE_IS_VALUE'	 , 0);
define('OPT_VALUE_IS_KEY'	 , 1);
define('OPT_LABEL_IS_VALUE'	 , 0);
define('OPT_LABEL_IS_KEY'	 , 2);
define('OPT_PERMANENTOBJECT' , 4);
define('OPT_LABEL2VALUE'	 , OPT_VALUE_IS_VALUE | OPT_LABEL_IS_KEY);
define('OPT_VALUE2LABEL'	 , OPT_VALUE_IS_KEY | OPT_LABEL_IS_VALUE);
define('OPT_VALUE'			 , OPT_VALUE_IS_VALUE | OPT_LABEL_IS_VALUE);
define('OPT_KEY'			 , OPT_VALUE_IS_KEY | OPT_LABEL_IS_KEY);

/**
 * Generate HTML option tag
 * 
 * @param string $elValue
 * @param string $label
 * @param boolean $selected
 * @param string $addAttr
 * @return string
 */
function htmlOption($elValue, $label=null, $selected=false, $addAttr='') {
	if( !$label ) { $label = $elValue; }
	return '<option '.valueField($elValue).($selected ? ' selected="selected"' : '').' '.$addAttr.'>'.$label.'</option>';
}

global $FORM_EDITABLE;
$FORM_EDITABLE	= true;

/**
 * Generate disabled HTML attribute
 * 
 * @return string 
 * 
 * Is this function useful ?
 */
function htmlDisabledAttr() {
	global $FORM_EDITABLE;
	return $FORM_EDITABLE ? '' : ' disabled';
}

/**
 * Generate HTML form intput name & value
 * 
 * @param string $fieldPath
 * @param string $default
 * @return string
 */
function formInput($fieldPath, $default=null) {
	return ' name="'.apath_html($fieldPath).'"'.inputValue($fieldPath, $default);
}

/**
 * Display formInput()
 * 
 * @param string $fieldPath
 * @param string $default
 */
function _formInput($fieldPath, $default=null) {
	echo formInput($fieldPath, $default);
}

/**
 * Get value of $fieldPath
 * 
 * @param string $fieldPath
 * @param string $default
 * @return string
 */
function valueOf($fieldPath, $default=null) {
	$value = null;
	fillInputValue($value, $fieldPath, $default);
	return $value!=null ? $value : '';
}

/**
 * Generate HTMl value attribute from $fieldPath
 * 
 * @param string $fieldPath
 * @param string $default
 * @return string
 */
function inputValue($fieldPath, $default=null) {
	$value = null;
	fillInputValue($value, $fieldPath, $default);
	return $value!=null ? valueField($value) : '';
}

/**
 * Display inputValue()
 * 
 * @param string $fieldPath
 * @param string $default
 */
function _inputValue($fieldPath, $default=null) {
	echo inputValue($fieldPath, $default);
}

/**
 * Generate HTML value attribute
 * 
 * @param string $v The value
 * @return string
 */
function valueField($v) {
	return ' value="'.addcslashes($v, '"').'"';
}

/**
 * Generate HTML upload input
 * 
 * @param string $fieldPath
 * @param string $addAttr
 * @return string
 */
function htmlFileUpload($fieldPath, $addAttr='') {
	return '<input type="file" name="'.apath_html($fieldPath).'" '.$addAttr.htmlDisabledAttr().'/>';
}

/**
 * Generate HTML password input
 *
 * @param string $fieldPath
 * @param string $addAttr
 * @return string
 */
function htmlPassword($fieldPath, $addAttr='') {
	return '<input type="password" name="'.apath_html($fieldPath).'" '.$addAttr.htmlDisabledAttr().'/>';
}

/**
 * Generate text input from parameters
 * 
 * @param string $fieldPath
 * @param string $default
 * @param string $addAttr
 * @param callback $formatter
 * @param string $type
 * @return string
 */
function htmlText($fieldPath, $default='', $addAttr='', $formatter=null, $type='text') {
	$value = null;
	fillInputValue($value, $fieldPath, $default);
	return '<input type="'.$type.'" name="'.apath_html($fieldPath).'" '.valueField(isset($value) ? isset($formatter) ? call_user_func($formatter, $value) : $value : '').' '.$addAttr.htmlDisabledAttr().'/>';
}

/**
 * Display htmlText()
 * 
 * @param string $fieldPath
 * @param string $default
 * @param string $addAttr
 * @param callback $formatter
 */
function _htmlText($fieldPath, $default='', $addAttr='', $formatter=null) {
	echo htmlText($fieldPath, $default, $addAttr, $formatter);
}

/**
 * Generate textarea from parameters
 * 
 * @param string $fieldPath
 * @param string $default
 * @param string $addAttr
 * @return string
 */
function htmlTextArea($fieldPath, $default='', $addAttr='') {
	$value = null;
	fillInputValue($value, $fieldPath, $default);
	return '<textarea name="'.apath_html($fieldPath).'" '.$addAttr.htmlDisabledAttr().'>'.$value.'</textarea>';
}

/**
 * Generate HTML hidden input
 *
 * @param string $fieldPath
 * @param string $default
 * @param string $addAttr
 * @return string
 */
function htmlHidden($fieldPath, $default='', $addAttr='') {
	$value = null;
	fillInputValue($value, $fieldPath, $default);
	return '<input type="hidden" name="'.apath_html($fieldPath).'" '.(isset($value) ? valueField($value).' ' : '').$addAttr.htmlDisabledAttr().'/>';
}

/**
 * Generate HTML radio input
 *
 * @param string $fieldPath
 * @param string $elValue
 * @param string $default
 * @param string $addAttr
 * @return string
 */
function htmlRadio($fieldPath, $elValue, $default=false, $addAttr='') {
	$value = null;
	$selected = fillInputValue($value, $fieldPath) ? $value==$elValue : $default;
	return '<input type="radio" name="'.apath_html($fieldPath).'" '.valueField($elValue).' '.($selected ? 'checked="checked"' : '').' '.$addAttr.htmlDisabledAttr().'/>';
}

/**
 * Generate HTML checkbox input
 *
 * @param string $fieldPath
 * @param string $value
 * @param string $default
 * @param string $addAttr
 * @return string
 */
function htmlCheckBox($fieldPath, $value=null, $default=false, $addAttr='') {
	// Checkbox : Null => Undefined, False => Unchecked, 'on' => Checked
	// 			If Value found,	we consider this one, else we use default
	$selected = false;
	fillInputValue($selected, $fieldPath, $default, true);
// 	debug("htmlCheckBox($fieldPath)", $selected);
// 	debug("is_array($selected) => ".b(is_array($selected)));
// 	debug("$value!==NULL && is_array($selected) && in_array($value, $selected) => ".b($value!==NULL && is_array($selected) && in_array($value, $selected)));
	return '<input type="checkbox" name="'.apath_html($fieldPath).($value!==NULL ? '[]' : '').'"'.(
		(
			($selected === true) || // Single value => TRUE
			($value!==NULL && is_array($selected) && in_array($value, $selected)) // Value is in array
		) ? ' checked' : '').($value!==NULL ? ' value="'.$value.'"' : '').' '.$addAttr.htmlDisabledAttr().'/>';
}

/**
 * Convert a apath to a HTML name attribute
 * 
 * @param string $apath
 * @return string
 * 
 * e.g user/password => user[password]
 */
function apath_html($apath) {
	$apath = explode('/', $apath);
	$htmlName = '';
	foreach( $apath as $index => $path ) {
		$htmlName .= ( $index ) ? '['.$path.']' : $path;
	}
	return $htmlName;
}

/** 
 * Get input form data
 * 
 * @return POST() or global $formData if set.
 *
 * Get input form data from POST.
 * Developers can specify an array of data to use by filling global $formData.
 * This function is designed to be used internally to have compliant way to get input form data.
 */
function getFormData() {
	return isset($GLOBALS['formData']) ? $GLOBALS['formData'] : POST();
}

/** 
 * Fill the given data from input form
 * 
 * @param $data The data to fill, as pointer.
 * @return The resulting $data.
 * @see getFormData()
 *
 * Fill the given pointer data array with input form data if null.
 * This function is designed to only offset the case where $data is null.
 */
function fillFormData(&$data) {
	return $data = is_null($data) ? getFormData() : $data;
}

/** 
 * Fill the given value from input form
 * 
 * @param $value The value to fill, as pointer.
 * @param $fieldPath The apath to the input form value.
 * @param $default The default value if not found. Default value is null (apath_get()'s default).
 * @param $pathRequired True if the path is required. Default value is False (apath_get()'s default).
 * @return boolean True if got value is not null (found).
 * @see getFormData()
 * @see apath_get()
 *
 * Fill the given pointer value with input form data or uses default.
 */
function fillInputValue(&$value, $fieldPath, $default=null, $pathRequired=false) {
	$value = apath_get(getFormData(), $fieldPath, $default, $pathRequired);
	if( $value === NULL ) {
		$value = $default;
	}
	return $value !== NULL;
}

/** 
 * Convert special characters to non-special ones
 * 
 * @param string $string The string to convert.
 * @return string The string wih no special characters.
 *
 * Replace all special characters in $string by the non-special version of theses.
 */
function convertSpecialChars($string) {
	// Replaces all letter special characters.
	// See http://stackoverflow.com/a/6837302/2610855
	// The answer is improved
	$string = str_replace(
		array(
			'À','à','Á','á','Â','â','Ã','ã','Ä','ä','Æ','æ','Å','å',
			'ḃ','Ḃ',
			'ć','Ć','ĉ','Ĉ','č','Č','ċ','Ċ','ç','Ç',
			'ď','Ď','ḋ','Ḋ','đ','Đ','ð','Ð',
			'é','É','è','È','ĕ','Ĕ','ê','Ê','ě','Ě','ë','Ë','ė','Ė','ę','Ę','ē','Ē',
			'ḟ','Ḟ','ƒ','Ƒ',
			'ğ','Ğ','ĝ','Ĝ','ġ','Ġ','ģ','Ģ',
			'ĥ','Ĥ','ħ','Ħ',
			'í','Í','ì','Ì','î','Î','ï','Ï','ĩ','Ĩ','į','Į','ī','Ī','ĵ',
			'Ĵ',
			'ķ','Ķ',
			'ĺ','Ĺ','ľ','Ľ','ļ','Ļ','ł','Ł',
			'ṁ','Ṁ',
			'ń','Ń','ň','Ň','ñ','Ñ','ņ','Ņ',
			'ó','Ó','ò','Ò','ô','Ô','ő','Ő','õ','Õ','ø','Ø','ō','Ō','ơ','Ơ','ö','Ö',
			'ṗ','Ṗ',
			'ŕ','Ŕ','ř','Ř','ŗ','Ŗ',
			'ś','Ś','ŝ','Ŝ','š','Š','ṡ','Ṡ','ş','Ş','ș','Ș','ß',
			'ť','Ť','ṫ','Ṫ','ţ','Ţ','ț','Ț','ŧ','Ŧ',
			'ú','Ú','ù','Ù','ŭ','Ŭ','û','Û','ů','Ů','ű','Ű','ũ','Ũ','ų','Ų','ū','Ū','ư','Ư','ü','Ü',
			'ẃ','Ẃ','ẁ','Ẁ','ŵ','Ŵ','ẅ','Ẅ',
			'ý','Ý','ỳ','Ỳ','ŷ','Ŷ','ÿ','Ÿ',
			'ź','Ź','ž','Ž','ż','Ż',
			'þ','Þ','µ','а','А','б','Б','в','В','г','Г','д','Д','е','Е','ё','Ё','ж','Ж','з','З','и','И','й','Й','к','К','л','Л','м','М','н','Н','о','О','п','П','р','Р','с','С','т','Т','у','У','ф','Ф','х','Х','ц','Ц','ч','Ч','ш','Ш','щ','Щ','ъ' => '', 'Ъ' => '', 'ы','Ы','ь' => '', 'Ь' => '', 'э','Э','ю','Ю','я','Я',
			' ','&'
		),
		array(
			'A','a','A','a','A','a','A','a','Ae','ae','AE','ae','A','a',
			'b','B',
			'c','C','c','C','c','C','c','C','c','C',
			'd','D','d','D','d','D','dh','Dh',
			'e','E','e','E','e','E','e','E','e','E','e','E','e','E','e','E','e','E',
			'f','F','f','F',
			'g','G','g','G','g','G','g','G',
			'h','H','h','H',
			'i','I','i','I','i','I','i','I','i','I','i','I','i','I','j',
			'J',
			'k','K',
			'l','L','l','L','l','L','l','L',
			'm','M',
			'n','N','n','N','n','N','n','N',
			'o','O','o','O','o','O','o','O','o','O','oe','OE','o','O','o','O','oe','OE',
			'p','P',
			'r','R','r','R','r','R',
			's','S','s','S','s','S','s','S','s','S','s','S','SS',
			't','T','t','T','t','T','t','T','t','T',
			'u','U','u','U','u','U','u','U','u','U','u','U','u','U','u','U','u','U','u','U','ue','UE',
			'w','W','w','W','w','W','w','W',
			'y','Y','y','Y','y','Y','y','Y',
			'z','Z','z','Z','z','Z',
			'th','Th','u','a','a','b','b','v','v','g','g','d','d','e','E','e','E','zh','zh','z','z','i','i','j','j','k','k','l','l','m','m','n','n','o','o','p','p','r','r','s','s','t','t','u','u','f','f','h','h','c','c','ch','ch','sh','sh','sch','sch','','','y','y','','','e','e','ju','ju','ja','ja',
			'_','and'
		), $string);
		//'','','','','','',''), $string);
	// Now replaces all other special character by nothing.
	$string = preg_replace('#[^a-z0-9\-\_\.]#i', '', $string);
	return $string;
}

/** 
 * Convert the string into a slug
 * 
 * @param $string The string to convert.
 * @param $case The case style to use, values: null (default), LOWERCAMELCASE or UPPERCAMELCASE.
 * @return string The slug version.
 *
 * Convert string to lower case and converts all special characters. 
 */
function toSlug($string, $case=null) {
	$string = str_replace(' ', '', ucwords(str_replace('&', 'and', strtolower($string))));
	if( isset($case) ) {
		if( bintest($case, CAMELCASE) ) {
			if( $case == LOWERCAMELCASE ) {
				$string = lcfirst($string);
			}
		}
	}
	return convertSpecialChars($string);
}

/** 
 * Convert the string into a slug
 * 
 * @param string $string The string to convert.
 * @param int $case The case style flag to use, values: null (default), LOWERCAMELCASE or UPPERCAMELCASE.
 * @return string The slug version.
 *
 * Convert string to lower case and converts all special characters. 
 */
function slug($string, $case=null) {
// 	$string = preg_replace('#[^a-z0-9\-_]#i', '', ucwords(str_replace('&', 'and',strtolower($string))));
	$string	= strtr(ucwords(str_replace('&', 'and', strtolower($string)))
		," .'\"", '----');
	if( isset($case) ) {
		if( bintest($case, CAMELCASE) ) {
			if( $case == LOWERCAMELCASE ) {
				$string = lcfirst($string);
			// } else
			// if( $case == UPPERCAMELCASE ) {
				// $string = ucfirst($string);
			}
		}
	}
	return convertSpecialChars($string);
}
defifn('CAMELCASE',			1<<0);
defifn('LOWERCAMELCASE',	CAMELCASE);
defifn('UPPERCAMELCASE',	CAMELCASE | 1<<1);

/** 
 * Get the string of a boolean
 * 
 * @param boolean $b The boolean
 * @return string The boolean's string
 */
function b($b) {
	return $b ? 'TRUE' : 'FALSE';
}

/** 
 * Split a string by string in limited values
 * 
 * @param string $delimiter The boundary string
 * @param string $string The input string
 * @param int $limit The limit of values exploded
 * @param string $default The default value to use if missing
 * @return array The exploded array with a defined limit of values.
 * @see explode()
 * 
 * Split a string by string in a limited number of values.
 * The main difference with explode() is this function complete missing values with $default.
 * If you want $limit optional, use explode()
 */
function explodeList($delimiter, $string, $limit, $default=null) {
	return array_pad(explode($delimiter, $string, $limit), abs($limit), $default);
}

/**
 * Hash string with salt
 * 
 * @param string $str
 * @return string
 * 
 * Hash input string with salt (constant USER_SALT) using SHA512
 */
function hashString($str) {
	//http://www.php.net/manual/en/faq.passwords.php
	$salt = defined('USER_SALT') ? USER_SALT : '1$@g&';
	return hash('sha512', $salt.$str.'7');
}

/** 
 * Get the date as string
 * 
 * @param string $datetime The datetime
 * @return string The date using 'dateFormat' translation key
 * 
 * Date format is storing a date, not a specific moment, we don't care about timezone
 */
function sql2Time($datetime) {
	return strtotime($datetime.' GMT');
}

/**
 * Format the date as string
 * 
 * @param mixed $time The UNIX timestamp
 * @param boolean $utc Is the time UTC
 * @return string The date using 'dateFormat' translation key
 * 
 * Date format is storing a date, not a specific moment, we don't care about timezone
 */
function d($time=TIME, $utc=false) {
// 	return !empty($time) ? strftime(t('dateFormat'), is_numeric($time) ? $time : strtotime($time)) : null;
	return df('dateFormat', $time, $utc ? false : null);
	// Dont care about timezone, this is UTC
	// Date is converted to midnight timestamp of this day, if we specify a tz < 0, the display date will be the previous one
	// return df('dateFormat', $time, false);
}

/**
 * Format the date time as string
 * 
 * @param mixed $time The UNIX timestamp
 * @param boolean $utc Is the time UTC
 * @return string The date using 'datetimeFormat' translation key
 * 
 * Datetime format is storing a specific moment, we care about timezone
 */
function dt($time=TIME, $utc=false) {
	return df('datetimeFormat', $time, $utc ? false : null);
}

/**
 * Format the date time as string
 * 
 * @param $format The format to use
 * @param mixed $time The UNIX timestamp
 * @param mixed $tz Timezone to use. False for UTC, Null for default or a string to specify the one to use
 * @return string The date formatted using $format
 * 
 * Datetime format is storing a specific moment, we care about timezone
 */
function df($format, $time=TIME, $tz=null) {
	if( $time === null ) {
		return '';
	}
	if( $tz === false ) {
		$tz = 'UTC';
	}
	if( $tz ) {
		$ctz = date_default_timezone_get();
		date_default_timezone_set($tz);
	}
// 	$r	= !empty($time) ? strftime(t($format), dateToTime($time)) : null;
	// Calculating some delay, we want 00:00 and not null
	$r	= strftime(t($format), dateToTime($time));
// 	debug("strftime(".t($format).", ".dateToTime($time)."[".$time."], $tz) => ".$r);
	if( isset($ctz) ) {
		date_default_timezone_set($ctz);
	}
	return $r;
}

/**
 * Convert date to time
 * 
 * @param int|string $date The date or UNIX timestamp
 * @return int The UNIX timestamp
 *
 * Allow any strtotime format to be converted to time, if time passed, it just returns it.
 */
function dateToTime($date) {
	if( is_numeric($date) ) {
		return $date;
	}
	if( $date instanceof DateTime ) {
		return $date->getTimestamp();
	}
	return strtotime($date.' GMT');
}

/** 
 * Get the date time as string
 * 
 * @param $time The time with %H:%M format.
 * @return string The formatted time using 'timeFormat' translation key
 * 
 * Convert the system time format to the user time format
 * The system uses the constant SYSTEM_TIME_FORMAT to get the default format '%H:%M', you can define it by yourself.
 */
function ft($time=null) {
	$userFormat	= translate('timeFormat', SYSTEM_TIME_FORMAT);
	if( $userFormat===SYSTEM_TIME_FORMAT ) { return $time; }
	$times	= parseTime(SYSTEM_TIME_FORMAT);
// 	if( !preg_match(timeFormatToRegex(SYSTEM_TIME_FORMAT), $time, $matches) ) {
// 		throw new Exception('invalidTimeParameter');
// 	}
	return strftime($userFormat, mktime($times[1], $times[2]));
// 	array_shift($matches);
// 	return str_replace(array('%H', '%M'), $matches, $userFormat);
}
defifn('SYSTEM_TIME_FORMAT', '%H:%M');

/**
 * Create time format regex from strftime format
 * 
 * @param string $format
 * @return string
 */
function timeFormatToRegex($format) {
	return '#^'.str_replace(array('%H', '%M'), array('([0-1][0-9]|2[0-3])', '([0-5][0-9])'), $format).'$#';
}

/**
 * Parse time from string to time array
 * 
 * @param string $time Parsed time
 * @param string $format Format to use
 * @return array
 * @throws Exception
 */
function parseTime($time, $format=SYSTEM_TIME_FORMAT) {
	$matches = null;
	if( !preg_match(timeFormatToRegex($format), $time, $matches) ) {
		throw new Exception('invalidTimeParameter');
	}
	array_shift($matches);
	return $matches;
}

/** 
 * Get the date as string in SQL format
 * 
 * @param int $time The UNIX timestamp.
 * @return string The date using sql format
 * 
 * Date format is storing a date, not a specific moment, we don't care about timezone
 */
function sqlDate($time=TIME) {
// 	return gmstrftime('%Y-%m-%d', $time);
	return strftime('%Y-%m-%d', $time);
}

/** 
 * Get the date time as string in SQL format
 * 
 * @param int $time The UNIX timestamp.
 * @return string The date using sql format
 * 
 * Datetime format is storing a specific moment, we care about timezone
 */
function sqlDatetime($time=TIME) {
	return gmstrftime('%Y-%m-%d %H:%M:%S', $time);
}

/** 
 * Get the client public IP
 * 
 * @return string The ip of the client
 */
function clientIP() {
	if( isset($_SERVER['REMOTE_ADDR']) ) {
		return $_SERVER['REMOTE_ADDR'];
	}
	if( isset($_SERVER['SSH_CLIENT']) ) {
		// [SSH_CLIENT] => REMOTE_IP REMOTE_PORT LOCAL_PORT
		return explode(' ', $_SERVER['SSH_CLIENT'], 2)[0];
		// else [SSH_CONNECTION] => REMOTE_IP REMOTE_PORT LOCAL_IP LOCAL_PORT
	}
	return '127.0.0.1';
}

/** 
 * Get the id of the current user
 * 
 * @return int|string The user's id
 */
function userID() {
	global $USER;
	return !empty($USER) ? $USER->id() : 0;
}

/** 
 * Generate a new password
 * 
 * @param integer $length The length of the generated password. Default value is 10.
 * @param string $chars The characters to use to generate password. Default value is 'abcdefghijklmnopqrstuvwxyz0123456789'
 * @return string The generated password.
 * 
 * Letters are randomly uppercased
 */
function generatePassword($length=10, $chars='abcdefghijklmnopqrstuvwxyz0123456789') {
	$max = strlen($chars)-1;
	$r = '';
	for( $i=0; $i<$length; $i++ ) {
		$c = $chars[mt_rand(0, $max)];
		$r .= mt_rand(0, 1) ? strtoupper($c) : $c;
	}
	return $r;
}

/**
 * Calculate the day timestamp using the given integer
 * 
 * @param int $time The time to get the day time. Default value is current timestamp.
 * @param boolean $gmt Is the time GMT
 * @return int
 * 
 * Return the timestamp of the current day of $time according to the midnight hour.
 */
function dayTime($time=null, $gmt=true) {
	if( $time === NULL ) { $time = time(); }
	return $time - $time%86400 - ($gmt ? date('Z') : 0);
}

/**
 * Return the timestamp of the $day of the month using the given integer
 * 
 * @param $day The day of the month to get the timestamp. Default value is 1, the first day of the month.
 * @param $time The time to get the month timestamp. Default value is current timestamp.
 * @return int
 * @see dayTime()
 *
 * Return the timestamp of the $day of current month of $time according to the midnight hour.
 */
function monthTime($day=1, $time=null) {
	if( $time === NULL ) { $time = time(); }
	return dayTime($time - (date('j', $time)-$day)*86400);
}

/**
 * Standardize the phone number to FR country format
 * 
 * @param $number The input phone number.
 * @param $delimiter The delimiter for series of digits. Default value is current timestamp. Default value is '.'.
 * @param $limit The number of digit in a serie separated by delimiter. Optional, the default value is 2.
 * @return string
 *
 * Return a standard phone number for FR country format.
 */
function standardizePhoneNumber_FR($number, $delimiter='.', $limit=2) {
	// If there is not delimiter we try to put one
	$number = str_replace(array('.', ' ', '-'), '', $number);
	$length	= strlen($number);
	if( $length < 10  ) { return ''; }
	$n = '';
	for( $i=strlen($number)-$limit; $i>3 || ($number[0]!='+' && $i>($limit-1)); $i-=$limit ) {
		$n = $delimiter.substr($number, $i, $limit).$n;
	}
	return substr($number, 0, $i+2).$n;
}

/**
 * Add zero to the input number
 * 
 * @param integer $number The number
 * @param integer $length The length to add zero
 * @return string
 */
function leadZero($number, $length=2) {
	return sprintf('%0'.$length.'d', $number);
}

/**
 * Format duration to closest unit
 * 
 * @param integer $duration Duration in seconds
 * @return string
 */
function formatDuration_Shortest($duration) {
	$formats = array('days'=>86400, 'hours'=>3600, 'minutes'=>60);
	foreach( $formats as $unit => $time ) {
		$r = $duration/$time;
		if( $r >= 1 ) { break; }
	}
	return t($unit.'_short', intval($r));
}

/**
 * Count intersect key in given arrays
 * 
 * @param array $array1
 * @param array $array2
 * @return int
 */
function count_intersect_keys($array1, $array2) {
	return count(array_intersect_key($array1, $array2));
}

/**
 * Get the mime type of the given file path
 * 
 * @param string $filePath
 * @return string
 */
function getMimeType($filePath) {
	if( function_exists('finfo_open') ) {
// 		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filePath);
	}
	return mime_content_type($filePath);
}

/**
 * Ensure path avaibility as folder
 * 
 * @param string $filePath
 * @return boolean
 */
function checkDir($filePath) {
	return is_dir($filePath) || mkdir($filePath, 0772, true);
}

/**
 * Insert $value in $array at $position
 * 
 * @param array $array
 * @param int $position
 * @param mixed $value
 * @set array_splice()
 */
function array_insert(&$array, $position, $value) {
	array_splice($array, $position, 0, $value);
}

/**
 * Add values from an array to another
 * 
 * @param array $array
 * @param array $other
 */
function array_add(&$array, $other) {
	$array = array_merge($array, $other);
}

/**
 * Filter $array entries by $keys
 *
 * @param array $array
 * @param array $keys
 * @return array
 */
function array_filterbykeys($array, $keys) {
	$r	= array();
	foreach( $keys as $key ) {
		if( array_key_exists($key, $array) ) {
			$r[$key] = $array[$key];
		}
	}
	return $r;
}

/**
 * Get the index in $array of $key
 *
 * @param array $array
 * @param scalar $key
 * @return int
 */
function array_index($array, $key) {
	return array_search($key, array_keys($array));
// 	return array_search($key, array_keys(array_values($array)));
}

/**
 * Get the last value of $array
 *
 * @param array $array
 * @return mixed
 */
function array_last($array) {
	// Copy of array, the pointer is not moved
	return end($array);
}

/**
 * Get value of $array at $index or $default if not found
 *
 * @param array $array
 * @param int $index 
 * @param boolean $default
 * @return mixed
 */
function array_get($array, $index, $default=false) {
	$array	= array_values($array);
// 	debug('array_get('.$index.') values', $array);
	return isset($array[$index]) ? $array[$index] : $default;
}

/**
 * Apply a user supplied function to every member of an array
 * 
 * @param array $array The input array.
 * @param callable $callback Typically, callback takes on two parameters. The array parameter's value being the first, and the key/index second.
 * @param string $userdata If the optional userdata parameter is supplied, it will be passed as the third parameter to the callback.
 * @param string $success TRUE on success or FALSE on failure.
 * @return array The resulting array
 */
function array_apply($array, $callback, $userdata=null, &$success=null) {
	$success	= array_walk($array, $callback, $userdata);
	return $array;
}

/**
 * Concat key and value in an array with a glue
 * @param string[] $array
 * @param string $peerGlue
 * @return array
 */
function array_peer($array, $peerGlue=': ') {
	return array_apply($array, function(&$v, $k) use($peerGlue) { $v = $k.$peerGlue.$v; });
}

/**
 * Make a string's first-only character uppercase
 * 
 * @param string $str
 * @return string
 */
function str_ucfirst($str) {
	return ucfirst(strtolower($str));
}

/**
 * Uppercase the first-only character of each word in a string
 * 
 * @param string $str
 * @return string
 */
function str_ucwords($str) {
	return ucwords(strtolower($str));
}

/**
 * Get the first char of a string
 * 
 * @param string $str
 * @return string
 */
function str_first($str) {
	return $str[0];
}

/**
 * Get the last char of a string
 * 
 * @param string $str
 * @return string
 */
function str_last($str) {
	return substr($str, -1);
}

/**
 * Reverse values
 * 
 * @param mixed $val1
 * @param mixed $val2
 */
function reverse_values(&$val1, &$val2) {
	$tmp	= $val1;
	$val1	= $val2;
	$val2	= $tmp;
}

/**
 * Check value in between min and max
 * 
 * @param integer $value
 * @param integer $min
 * @param integer $max
 * @return boolean
 */
function between($value, $min, $max) {
	return $min <= $value && $value <= $max;
}

/**
 * Delete a HTTP cookie
 *
 * @param string $name The name of the cookie to delete
 * @return boolean True if cookie was deleted, false if not found
 */
function deleteCookie($name) {
	if( !isset($_COOKIE[$name]) ) {
		return false;
	}
	unset($_COOKIE[$name]);
	setcookie($name, '', 1, '/');
	return true;
}

/**
 * Start a PHP Session
 *
 * @param mixed $type The type flag of the session
 * @throws UserException
 * 
 * Start a secured PHP Session and initialize Orpheus
 */
function startSession($type=SESSION_WITH_COOKIE) {
	/**
	 * By default, browsers share cookies across subdomains
	 * So, we change the session name (also the cookie name) according to host
	 * and specify host as .domain.com (prefixed by a dot).
	 */
	if( session_status() !== PHP_SESSION_NONE ) {
		// Already started
		return;
	}
	
	Hook::trigger(HOOK_STARTSESSION, $type);
	if( bintest($type, SESSION_WITH_COOKIE) ) {
		defifn('SESSION_COOKIE_LIFETIME',	86400*7);
		// Set session cookie parameters, HTTPS session is only HTTPS
		// Never set the domain, it will apply to subdomains
		// domain.com shares cookies with all subdomains... HTTP made me cry
		session_set_cookie_params(SESSION_COOKIE_LIFETIME, PATH, SESSION_SHARE_ACROSS_SUBDOMAIN ? '' : '.'.HOST, HTTPS, true);
// 		session_set_cookie_params(SESSION_COOKIE_LIFETIME, PATH, '', HTTPS, true);
// 		session_set_cookie_params(SESSION_COOKIE_LIFETIME, PATH, HOST, HTTPS, true);
	}
// 	if( bintest($type, SESSION_WITH_HTTPTOKEN) ) {
// 		$_SERVER['HTTP_ACCEPT_AUTHORIZATION']
		// Authorization: Bearer TOKEN
// 		defifn('SESSION_COOKIE_LIFETIME',	86400*7);
// 		// Set session cookie parameters, HTTPS session is only HTTPS
// 		session_set_cookie_params(SESSION_COOKIE_LIFETIME, PATH, HOST, HTTPS, true);
// 	}

	// Make cookie domain-dependant
	session_name('PHPSESSID'.(SESSION_SHARE_ACROSS_SUBDOMAIN ? '' : sprintf("%u", crc32(HOST))));

	// PHP is unable to manage exception thrown during session_start()
	$GLOBALS['ERROR_ACTION'] = ERROR_DISPLAY_RAW;
	session_start();
	$GLOBALS['ERROR_ACTION'] = ERROR_THROW_EXCEPTION;
	
	$initSession	= function() {
		$_SESSION	= array('ORPHEUS' => array('LAST_REGENERATEID'=>TIME, 'CLIENT_IP'=>clientIP()));
		if( defined('SESSION_VERSION') ) {
			$_SESSION['ORPHEUS']['SESSION_VERSION']	= SESSION_VERSION;
		}
	};
	if( !isset($_SESSION['ORPHEUS']) ) {
		$initSession();
	} else // Outdated session version
	if( defined('SESSION_VERSION') && (!isset($_SESSION['ORPHEUS']['SESSION_VERSION']) || floor($_SESSION['ORPHEUS']['SESSION_VERSION']) != floor(SESSION_VERSION)) ) {
		$initSession();
		throw new UserException('outdatedSession');
	} else // Old session (Will be removed)
	if( !isset($_SESSION['ORPHEUS']['CLIENT_IP']) ) {
		$_SESSION['ORPHEUS']['CLIENT_IP']	= clientIP();
	} else // Hack Attemp' - Session stolen
	if( Config::get('session_moved_allow', false) && $_SESSION['ORPHEUS']['CLIENT_IP'] != clientIP() ) {
		// It will return hack attemp' even if user is using a VPN
		// Allow 'reset', 'home', 'exception' / Default is 'reset'
		$movedAction = Config::get('moved_session_action', 'home');
		// reset in all cases
		$initSession();
		if( $movedAction === 'home' ) {
			redirectTo(DEFAULTLINK);
		} else
		if( $movedAction === 'exception' ) {
			throw new UserException('movedSession');
		}
	}
	
	Hook::trigger(HOOK_SESSIONSTARTED, $type);
}
defifn('SESSION_SHARE_ACROSS_SUBDOMAIN',	false);
define('SESSION_WITH_COOKIE',		1<<0);
define('SESSION_WITH_HTTPTOKEN',	1<<1);

/**
 * Calculate age from $birthday $relativeTo a date
 * 
 * @param int $birthday
 * @param string $relativeTo
 * @return int
 */
function calculateAge($birthday, $relativeTo='today') {
	return date_diff(date_create($birthday), date_create($relativeTo))->y;
}

/**
 * Find whether the given variable is a closure
 * 
 * @param mixed $v
 * @return boolean True if $v is a closure
 */
function is_closure($v) {
	return is_object($v) && ($v instanceof \Closure);
}

/**
 * Test the given variable is an exception
 *
 * @param mixed $e
 * @return boolean True if $v is an Exception
 */
function is_exception($e) {
	return is_object($e) && ($e instanceof Exception);
}

/**
 * Get microsecond as UNIX format
 *
 * @return number|string
 */
function ms($precision=null) {
	return $precision !== null ? number_format(microtime(true), $precision, '.', '') : round(microtime(true)*1000);
}
