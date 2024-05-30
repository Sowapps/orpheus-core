<?php /** @noinspection PhpComposerExtensionStubsInspection */

/**
 * The core functions
 * PHP File containing all system functions.
 *
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

use Orpheus\Config\Config;
use Orpheus\Exception\UserException;
use Orpheus\Exception\UserReportsException;
use Orpheus\InputController\HttpController\HttpRequest;
use Orpheus\Service\SecurityService;

/**
 * Do a binary test to check $value is matching $reference
 *
 * @param int $value The value to compare.
 * @param int $reference The reference for the comparison.
 * @return bool True if $value is binary included in $reference.
 * Do a binary test, compare $value with $reference.
 * This function is very useful to do binary comparison for rights and inclusion in a value.
 */
function matchBits(int $value, int $reference): bool {
	return (($value & $reference) === $reference);
}

/**
 * Scans a directory cleanly.
 *
 * @param string $dir The path to the directory to scan.
 * @param bool $reverseOrder True to reverse results order. Default value is False.
 * @return string[] An array of the files in this directory.
 * @noinspection PhpAutovivificationOnFalseValuesInspection
 */
function scanFolder(string $dir, bool $reverseOrder = false): array {
	//	try {
	// Sort after to remove 0 and 1 ("." and "..")
		$result = scandir($dir);
	//	} catch( Exception ) {
	//		return [];
	//	}
	unset($result[0]);
	unset($result[1]);
	if( $reverseOrder ) {
		rsort($result);
	}
	
	return $result;
}

/**
 * Stringify any variable
 *
 * @param mixed $s the input data to stringify
 */
function stringify(mixed $s): string {
	if( $s instanceof Exception ) {
		$s = formatException($s);
	} else {
		$s = "\n" . print_r($s, 1);
	}
	
	return $s;
}

/**
 * Convert a variable to an HTML-readable string
 *
 * @param mixed $value the input data to stringify
 * @see toString()
 */
function toHtml(string|int|bool|null $value): string {
	if( $value === null ) {
		$value = '{NULL}';
	} else if( $value === false ) {
		$value = '{FALSE}';
	} else if( $value === true ) {
		$value = '{TRUE}';
	} else {
		if( !is_scalar($value) ) {
			$value = strval($value);
		}
		$value = '<pre>' . print_r($value, 1) . '</pre>';
	}
	
	return $value;
}

/**
 * Convert a variable a Text-readable string
 *
 * @param mixed $value the input data to stringify
 * @see toHtml()
 */
function toString(mixed $value): string {
	if( $value === null ) {
		$value = 'NULL';
	} else if( $value === false ) {
		$value = 'FALSE';
	} else if( $value === true ) {
		$value = 'TRUE';
	} else if( is_array($value) || is_object($value) ) {
		$value = json_encode($value);
	} else {
		if( !is_scalar($value) ) {
			$value = strval($value);
		}
		$value = print_r($value, 1);
	}
	
	return $value;
}

/**
 * Format the input Exception to a human-readable string
 *
 */
function formatException(Throwable $e): string {
	return sprintf('Exception "%s" with %s in %s:%d%s<pre>%s</pre>',
		get_class($e), $e->getMessage() ? " message '{$e->getMessage()}'" : 'no message', $e->getFile(), $e->getLine(), PHP_EOL, $e->getTraceAsString());
}

/**
 * Get the debug trace filtered by $filterStartWith
 *
 * @param string|null $filterStartWith Exclude functions starting with this value
 * @return array The filtered backtrace
 */
function getDebugTrace(?string $filterStartWith = null): array {
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
 * Log a report in a file.
 * Data are serialized to JSON.
 * Each line of the file is a JSON string of the reports.
 * The log folder is the constant LOGS_PATH.
 *
 * @param string|Exception $report The report to log.
 * @param string $file The log file path.
 * @param string|null $action The action associated to the report. Default value is an empty string.
 * @warning This function require a writable log file.
 */
function log_report(string|Throwable $report, string $file, ?string $action = null): void {
	$exception = null;
	if( !is_scalar($report) ) {
		if( $report instanceof Throwable ) {
			$exception = $report;
			$report = $exception->getMessage();
		} else {
			$report = stringify($report);
		}
	}
	$error = [
		'id'     => uniqid('OL', true),
		'date'   => date('c'),
		'file' => $exception?->getFile(),
		'line' => $exception?->getLine(),
		'report' => $report,
		'action' => $action,
		'trace'  => $exception ? $exception->getTrace() : getDebugTrace('log'),
		'crc32'  => crc32(isset($exception) ? formatException($exception) : $report) . '',
	];
	if( !is_dir(LOGS_PATH) ) {
		mkdir(LOGS_PATH, 0777, true);
	}
	$logFilePath = LOGS_PATH . '/' . $file;
	try {
		file_put_contents($logFilePath, json_encode($error) . "\n", FILE_APPEND);
	} catch( Throwable $e ) {
		processException($e, false);
	}
}

/**
 * Log a debug
 *
 * Log a debug. The log file is the constant LOGFILE_DEBUG.
 *
 * @param string $report The debug report to log.
 * @param string|null $action The action associated to the report. Default value is an empty string.
 * @see log_report()
 */
function log_debug(string $report, ?string $action = null): void {
	log_report($report, LOGFILE_DEBUG, $action);
}

/**
 * Log a hack attempt
 * The log file is the constant LOGFILE_HACK or, if undefined, '.hack'.
 *
 * @param string $report The report to log.
 * @param string|null $action The action associated to the report. Default value is an empty string.
 * @see log_report()
 */
function log_hack(string $report, ?string $action = null): void {
	$user = SecurityService::get()->getActiveUser();
	log_report($report . '
[ IP: ' . clientIp() . '; User: ' . ($user ? "$user #" . $user->id() : 'N/A') . '; agent: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') . '; referer: ' . ($_SERVER['HTTP_REFERER'] ?? 'N/A') . ' ]',
		LOGFILE_HACK, $action);
}

/**
 * Log a system error
 * The log file is the constant LOGFILE_SYSTEM or, if undefined, '.log_error'.
 *
 * @param string|Throwable $report The report to log.
 * @param string|null $action The action associated to the report. Default value is an empty string.
 * @see log_report()
 */
function log_error(string|Throwable $report, ?string $action = null): void {
	if( defined('LOGFILE_SYSTEM') ) {
		log_report($report, LOGFILE_SYSTEM, $action);
	}// Prevents error accumulation
}

/**
 * Log a sql error
 * The log file is the constant LOGFILE_SQL or, if undefined, '.pdo_error'.
 *
 * @param string|Exception $report The report to log
 * @param string|null $action The action associated to the report. Default value is an empty string
 * @see log_report()
 */
function sql_error(string|Throwable $report, ?string $action = null): void {
	log_report($report, LOGFILE_SQL, $action);
}

/**
 * Convert Text to HTML
 *
 * @param string $text The string to convert
 */
function html(string $text): string {
	return nl2br(escapeText($text));
}

/**
 * Format a string to be a html attribute value
 *
 * @param mixed $value The variable to format
 * @return string The escaped string
 */
function htmlAttribute(mixed $value): string {
	if( !is_scalar($value) ) {
		$value = json_encode($value);
	}
	$flags = ENT_QUOTES | ENT_IGNORE;
	if( defined('ENT_HTML5') ) {
		$flags |= ENT_HTML5;
	}
	
	return htmlentities($value, $flags, 'UTF-8', false);
}

/**
 * Get value from an Array Path
 *
 * Get value from an Array Path using / as separator.
 * Return null if parameters are invalids, $default if the path is not found else the value.
 * If $default is not null and returned value is null, you can infer your parameters are invalids.
 *
 * @param array $array The array to get the value from.
 * @param string $path The path used to browse the array.
 * @param mixed $default The default value returned if array is valid but key is not found.
 * @param bool $pathRequired True if the path is required. Default value is False.
 * @return mixed The value from $path in $array.
 */
function array_path_get(array $array, string $path, mixed $default = null, bool $pathRequired = false): mixed {
	if( !$array || !$path ) {
		return $default;
	}
	[$key, $suffix] = explodeList('/', $path, 2);
	// If element does not exist in array
	if( !isset($array[$key]) ) {
		// If having a child, the child could not be found
		// Else container exists, but element not found.
		return ($pathRequired && $suffix !== null) ? null : $default;
	}
	
	return ($suffix === null || $suffix === '') ? $array[$key] : array_path_get($array[$key], $suffix);
}

/**
 * Set value into array using an Array Path with / as separator.
 *
 * @param array|null $array $array The array to get the value from.
 * @param string $path The path used to browse the array.
 * @param mixed $value The value to set in array
 * @param bool $overwrite True to overwrite existing value. Default value is True.
 * @see array_path_get()
 */
function array_path_set(?array &$array, string $path, mixed $value, bool $overwrite = true): void {
	$array ??= [];
	[$key, $suffix] = explodeList('/', $path, 2);
	// The path ends here
	if( $suffix === null || $suffix === '' ) {
		// NULL value will always be overwritten
		if( $overwrite || !isset($array[$key]) ) {
			$array[$key] = $value;
		}
		
		return;
	}
	$array[$key] ??= [];
	array_path_set($array[$key], $suffix, $value, $overwrite);
}

/**
 * Starts a new report stream, all new reports will be added to this stream.
 *
 * @param string $stream The new report stream name
 * @see endReportStream()
 */
function startReportStream(string $stream): void {
	$GLOBALS['REPORT_STREAM'] = $stream;
}

/**
 * Ends the current stream by setting current stream to the global one, so you can not end global stream.
 *
 * @see startReportStream()
 */
function endReportStream(): void {
	startReportStream('global');
}

endReportStream();

/**
 * Transfers the stream reports to another
 *
 * @param string|null $from Transfers $from this stream. Default value is null (current stream).
 * @param string $to Transfers $to this stream. Default value is global.
 */
function transferReportStream(?string $from = null, string $to = 'global'): bool {
	if( is_null($from) ) {
		$from = $GLOBALS['REPORT_STREAM'];
	}
	if( $from === $to ) {
		return false;
	}
	global $REPORTS;
	if( !empty($REPORTS[$from]) ) {
		if( !isset($REPORTS[$to]) ) {
			$REPORTS[$to] = [];
		}
		$REPORTS[$to] = isset($REPORTS[$to]) ? array_merge_recursive($REPORTS[$to], $REPORTS[$from]) : $REPORTS[$from];
		unset($REPORTS[$from]);
	}
	
	return true;
}

/**
 * Add a report
 *
 * Add the report $message to the list of reports for this $type.
 * The type of the message is commonly 'success' or 'error'.
 *
 * @param mixed $report The report (Commonly a string or an UserException).
 * @param string $type The type of the message.
 * @param string|null $domain The domain to use to automatically translate the message. Default value is 'global'.
 * @param string|null $code The key code to use for this report. Default is $report.
 * @param int $severity The severity of report. Default value is 0.
 * @return bool False if rejected
 * @see reportSuccess(), reportError()
 */
function addReportWith(string $report, string $type, ?string $domain = null, ?string $code = null, int $severity = 0): bool {
	$domain ??= 'global';
	$code ??= $report;
	$report = t($report, $domain);
	return addReport(['code' => $code, 'report' => $report, 'domain' => $domain, 'severity' => $severity], $type);
}

function addReport(array $report, string $type): bool {
	global $REPORTS, $REPORT_STREAM, $DISABLE_REPORT;
	if( !empty($DISABLE_REPORT) ) {
		return false;
	}
	$REPORTS[$REPORT_STREAM] ??= [];
	$REPORTS[$REPORT_STREAM][$type] ??= [];
	$REPORTS[$REPORT_STREAM][$type][] = $report;
	
	return true;
}

/**
 * Report a success
 *
 * Adds the report $message to the list of reports for this type 'success'.
 *
 * @param mixed $report The message to report.
 * @param string|null $domain The domain fo the message. Not used for translation. Default value is global.
 * @return bool False if rejected
 * @see addReportWith()
 */
function reportSuccess(mixed $report, ?string $domain = null): bool {
	return addReportWith($report, 'success', $domain);
}

/**
 * Reports an information to the user
 *
 * Adds the report $message to the list of reports for this type 'info'.
 *
 * @param mixed $report The message to report.
 * @param string|null $domain The domain fo the message. Not used for translation. Default value is global.
 * @return bool False if rejected
 * @see addReportWith()
 */
function reportInfo(mixed $report, ?string $domain = null): bool {
	return addReportWith($report, 'info', $domain);
}

/**
 * Reports a warning
 *
 * Adds the report $message to the list of reports for this type 'warning'.
 * Warning come in some special cases, we meet it when we do automatic checks before loading contents and there is something to report to the user.
 *
 * @param mixed $report The message to report.
 * @param string|null $domain The domain fo the message. Not used for translation. Default value is the domain of Exception in case of UserException else 'global'.
 * @return bool False if rejected
 * @see addReportWith()
 */
function reportWarning(mixed $report, ?string $domain = null): bool {
	return reportError($report, $domain, 0);
}

/**
 * Reports an error
 *
 * @param mixed $report The report.
 * @param string|null $domain The domain fo the message. Default value is the domain of Exception in case of UserException else 'global'.
 * @param int $severity The severity of the error, commonly 1 for standard user error and 0 for warning. Default value is 1.
 * @return bool False if rejected
 * @see addReportWith()
 * Adds the report $message to the list of reports for this type 'error'.
 */
function reportError(mixed $report, ?string $domain = null, int $severity = 1): bool {
	$code = null;
	if( $report instanceof UserReportsException ) {
		foreach( $report->getReports() as $childReport ) {
			addReport($childReport, 'error');
		}
	}
	if( $report instanceof UserException ) {
		$code = $report->getMessage();
		$domain ??= $report->getDomain();
	}
	
	return addReportWith($report, 'error', $domain, $code, $severity);
}

/**
 * Check if there is error reports
 *
 * @return bool True if there is any error report.
 */
function hasErrorReports(): bool {
	global $REPORTS;
	if( empty($REPORTS) ) {
		return false;
	}
	foreach( $REPORTS as $types ) {
		if( !empty($types['error']) ) {
			return true;
		}
	}
	
	return false;
}

/**
 * Get some/all reports
 *
 * Get all reports from the list of $domain optionally filtered by type.
 *
 * @param string $stream The stream to get the reports. Default value is "global".
 * @param string|null $type Filter results by report type. Default value is null.
 * @param bool $delete True to delete entries from the list. Default value is true.
 * @see formatReportListToHtml()
 */
function getReports(string $stream = 'global', ?string $type = null, bool $delete = true): array {
	global $REPORTS;
	if( empty($REPORTS[$stream]) ) {
		return [];
	}
	// Type specified
	if( $type ) {
		if( empty($REPORTS[$stream][$type]) ) {
			return [];
		}
		$r = $REPORTS[$stream][$type];
		if( $delete ) {
			unset($REPORTS[$stream][$type]);
		}
		
		return [$type => $r];
	}
	// All types
	$r = $REPORTS[$stream];
	if( $delete ) {
		$REPORTS[$stream] = [];
	}
	
	return $r;
}

/**
 * Get some/all reports as flatten array
 *
 * Get all reports from the list of $domain optionally filtered by type.
 *
 * @param string $stream The stream to get the reports. Default value is "global".
 * @param string|null $type Filter results by report type. Default value is null.
 * @param bool $delete True to delete entries from the list. Default value is true.
 * @return array[].
 * @see getReports()
 */
function getFlatReports(string $stream = 'global', ?string $type = null, bool $delete = true): array {
	$reports = [];
	foreach( getReports($stream, $type, $delete) as $rType => $rTypeReports ) {
		foreach( $rTypeReports as $report ) {
			$report['type'] = $rType;
			$reports[] = $report;
		}
	}
	
	return $reports;
}

/**
 * Get some/all reports as HTML
 *
 * Get all reports from the list of $domain and generates the HTML source to display.
 *
 * @param string $stream The stream to get the reports. Default value is 'global'.
 * @param array $rejected An array of rejected messages. Default value is an empty array.
 * @param bool $delete True to delete entries from the list. Default value is true.
 * @return string The renderer HTML.
 * @see displayReportsHtml()
 * @see formatReportToHtml()
 */
function formatReportListToHtml(string $stream = 'global', array $rejected = [], bool $delete = true): string {
	$reports = getReports($stream, null, $delete);
	if( !$reports ) {
		return '';
	}
	$reportHTML = '';
	foreach( $reports as $type => $typeReports ) {
		foreach( $typeReports as $report ) {
			$message = strval($report['report']);
			if( !in_array($message, $rejected) ) {
				$reportHTML .= formatReportToHtml($stream, $message, $report['domain'], $type);
			}
		}
	}
	
	return $reportHTML;
}

/**
 * Get one report as HTML
 *
 * @param string $stream The stream of the report.
 * @param string $report The message to report.
 * @param string $domain The domain of the report.
 * @param string $type The type of the report.
 */
function formatReportToHtml(string $stream, string $report, string $domain, string $type): string {
	return sprintf('<div class="report report_%s %s %s">%s</div>', $stream, $type, $domain, nl2br($report));
}

/**
 * Displays all reports from the list of $domain and displays generated HTML source.
 *
 * @param string $stream The stream to display. Default value is 'global'.
 * @param string[] $rejected An array of rejected messages. Can be the first parameter.
 * @param bool $delete True to delete entries from the list.
 * @see formatReportListToHtml()
 */
function displayReportsHtml(string $stream = 'global', array $rejected = [], bool $delete = true): void {
	echo sprintf('<div class="reports %s">%s</div>', $stream, formatReportListToHtml($stream, $rejected, $delete));
}

/**
 * Generate the HTML source for options of a select tag
 * For associative arrays, we commonly use the value=>label model (OPT_VALUE2LABEL) but sometimes for associative arrays we could prefer the label=>value model (OPT_LABEL2VALUE).
 * You can use your own combination with defined constants OPT_VALUE_IS_VALUE, OPT_VALUE_IS_KEY, OPT_LABEL_IS_VALUE and OPT_LABEL_IS_KEY.
 * Common combinations are OPT_LABEL2VALUE, OPT_VALUE2LABEL and OPT_VALUE.
 * The label is prefixed with $prefix and translated using t(). This function allows bi-dimensional arrays in $values, used as option group.
 *
 * @param string|null $fieldPath The name path to the field.
 * @param array $values The values to build the dropdown menu.
 * @param null $default The default selected value. Default value is null (no selection).
 * @param int|null $matches Define the associativity between array and option values. Default value is OPT_VALUE2LABEL (as null).
 * @param string $prefix The prefix to use for the text name of values. Default value is an empty string.
 * @param string $domain The domain to apply the Key. Default value is 'global'.
 * @return string An HTML source for the built SELECT tag.
 * @see htmlOption()
 */
function htmlOptions(?string $fieldPath, array $values, mixed $default = null, int $matches = null, string $prefix = '', string $domain = 'global'): string {
	if( $matches === null ) {
		$matches = OPT_VALUE2LABEL;
	}
	// Value of selected/default option
	$selValue = null;
	if( $fieldPath ) {
		fillInputValue($selValue, $fieldPath, OPT_ENTITY && is_object($default) ? $default->id() : $default);
	} else {
		$selValue = $default;
	}
	$optionsHtml = '';
	foreach( $values as $dataKey => $elValue ) {
		if( $elValue === null ) {
			continue;
		}
		if( is_array($elValue) ) {
			$optionsHtml .= '<optgroup label="' . t($prefix . $dataKey, $domain) . '">' . htmlOptions($fieldPath, $elValue, $default, $matches, $prefix, $domain) . '</optgroup>';
			continue;
		}
		$addAttr = '';
		if( is_array($elValue) ) {
			[$elValue, $addAttr] = array_pad($elValue, 2, null);
		}
		if( matchBits($matches, OPT_ENTITY) ) {
			$optLabel = "$elValue";
			$optValue = $elValue->id();
		} else {
			$optLabel = matchBits($matches, OPT_LABEL_IS_KEY) ? $dataKey : $elValue;
			$optValue = matchBits($matches, OPT_VALUE_IS_KEY) ? $dataKey : $elValue;
		}
		$optionsHtml .= htmlOption($optValue, t($prefix . $optLabel, $domain), is_array($selValue) ? in_array("$optValue", $selValue) : "$selValue" === "$optValue", $addAttr);
	}
	
	return $optionsHtml;
}

const OPT_VALUE_IS_VALUE = 0;
const OPT_VALUE_IS_KEY = 1;
const OPT_LABEL_IS_VALUE = 0;
const OPT_LABEL_IS_KEY = 2;
const OPT_ENTITY = 4;
const OPT_LABEL2VALUE = OPT_VALUE_IS_VALUE | OPT_LABEL_IS_KEY;
const OPT_VALUE2LABEL = OPT_VALUE_IS_KEY | OPT_LABEL_IS_VALUE;
const OPT_VALUE = OPT_VALUE_IS_VALUE | OPT_LABEL_IS_VALUE;
const OPT_KEY = OPT_VALUE_IS_KEY | OPT_LABEL_IS_KEY;

/**
 * Generate HTML option tag
 *
 */
function htmlOption(string $elValue, ?string $label = null, bool $selected = false, string $addAttr = ''): string {
	if( !$label ) {
		$label = $elValue;
	}
	
	return '<option ' . valueField($elValue) . ($selected ? ' selected="selected"' : '') . ' ' . $addAttr . '>' . $label . '</option>';
}

/**
 * @deprecated
 */
global $FORM_EDITABLE;
$FORM_EDITABLE = true;

/**
 * Generate disabled HTML attribute
 *
 */
function htmlDisabledAttr(): string {
	global $FORM_EDITABLE;
	
	return $FORM_EDITABLE ? '' : ' disabled';
}

/**
 * Generate HTML value attribute
 *
 * @param string $value The value
 */
function valueField(string $value): string {
	return ' value="' . addcslashes($value, '"') . '"';
}

/**
 * Convert a path to an HTML name attribute
 * E.g. user/password => user[password]
 *
 */
function array_path_html(string $path): string {
	$path = explode('/', $path);
	$htmlName = '';
	foreach( $path as $index => $pathPart ) {
		$htmlName .= ($index) ? '[' . $pathPart . ']' : $pathPart;
	}
	
	return $htmlName;
}

/**
 * Get post form data
 *
 */
function getFormData(): array {
	if( isSupportingInputController() ) {
		return HttpRequest::getMainHttpRequest()->getAllData();
	}
	
	// Deprecated way
	return $_POST;
}

function isSupportingInputController(): bool {
	return class_exists(HttpRequest::class);
}

/**
 * Fill the given data from input form
 *
 * @param array|null $data The data to fill, as pointer.
 * @return array The resulting $data.
 * @see getFormData()
 *
 * Fill the given pointer data array with input form data if null.
 * This function is designed to only offset the case where $data is null.
 */
function fillFormData(?array &$data): array {
	return $data = is_null($data) ? getFormData() : $data;
}

/**
 * Fill the given value from input form
 *
 * @param mixed $value The value to fill, as pointer.
 * @param string $fieldPath The array_path to the input form value.
 * @param string|null $default The default value if not found. Default value is null (array_path_get()'s default).
 * @param bool $pathRequired True if the path is required. Default value is False (array_path_get()'s default).
 * @return bool True if got value is not null (found).
 * @see getFormData()
 * @see array_path_get()
 *
 * Fill the given pointer value with input form data or uses default.
 */
function fillInputValue(mixed &$value, string $fieldPath, mixed $default = null, bool $pathRequired = false): bool {
	$value = array_path_get(getFormData(), $fieldPath, $default, $pathRequired);
	if( $value === null ) {
		$value = $default;
	}
	
	return $value !== null;
}

/**
 * Convert special characters to non-special ones
 * Replace all special characters in $string by the non-special version of theses.
 *
 * @param string $string The string to convert
 * @return string The string wih no special characters
 */
function convertSpecialChars(string $string): string {
	// Replaces all letter special characters.
	// See http://stackoverflow.com/a/6837302/2610855
	// The answer is improved
	$string = str_replace(
		[
			'À', 'à', 'Á', 'á', 'Â', 'â', 'Ã', 'ã', 'Ä', 'ä', 'Æ', 'æ', 'Å', 'å',
			'ḃ', 'Ḃ',
			'ć', 'Ć', 'ĉ', 'Ĉ', 'č', 'Č', 'ċ', 'Ċ', 'ç', 'Ç',
			'ď', 'Ď', 'ḋ', 'Ḋ', 'đ', 'Đ', 'ð', 'Ð',
			'é', 'É', 'è', 'È', 'ĕ', 'Ĕ', 'ê', 'Ê', 'ě', 'Ě', 'ë', 'Ë', 'ė', 'Ė', 'ę', 'Ę', 'ē', 'Ē',
			'ḟ', 'Ḟ', 'ƒ', 'Ƒ',
			'ğ', 'Ğ', 'ĝ', 'Ĝ', 'ġ', 'Ġ', 'ģ', 'Ģ',
			'ĥ', 'Ĥ', 'ħ', 'Ħ',
			'í', 'Í', 'ì', 'Ì', 'î', 'Î', 'ï', 'Ï', 'ĩ', 'Ĩ', 'į', 'Į', 'ī', 'Ī', 'ĵ',
			'Ĵ',
			'ķ', 'Ķ',
			'ĺ', 'Ĺ', 'ľ', 'Ľ', 'ļ', 'Ļ', 'ł', 'Ł',
			'ṁ', 'Ṁ',
			'ń', 'Ń', 'ň', 'Ň', 'ñ', 'Ñ', 'ņ', 'Ņ',
			'ó', 'Ó', 'ò', 'Ò', 'ô', 'Ô', 'ő', 'Ő', 'õ', 'Õ', 'ø', 'Ø', 'ō', 'Ō', 'ơ', 'Ơ', 'ö', 'Ö',
			'ṗ', 'Ṗ',
			'ŕ', 'Ŕ', 'ř', 'Ř', 'ŗ', 'Ŗ',
			'ś', 'Ś', 'ŝ', 'Ŝ', 'š', 'Š', 'ṡ', 'Ṡ', 'ş', 'Ş', 'ș', 'Ș', 'ß',
			'ť', 'Ť', 'ṫ', 'Ṫ', 'ţ', 'Ţ', 'ț', 'Ț', 'ŧ', 'Ŧ',
			'ú', 'Ú', 'ù', 'Ù', 'ŭ', 'Ŭ', 'û', 'Û', 'ů', 'Ů', 'ű', 'Ű', 'ũ', 'Ũ', 'ų', 'Ų', 'ū', 'Ū', 'ư', 'Ư', 'ü', 'Ü',
			'ẃ', 'Ẃ', 'ẁ', 'Ẁ', 'ŵ', 'Ŵ', 'ẅ', 'Ẅ',
			'ý', 'Ý', 'ỳ', 'Ỳ', 'ŷ', 'Ŷ', 'ÿ', 'Ÿ',
			'ź', 'Ź', 'ž', 'Ž', 'ż', 'Ż',
			'þ', 'Þ', 'µ', 'а', 'А', 'б', 'Б', 'в', 'В', 'г', 'Г', 'д', 'Д', 'е', 'Е', 'ё', 'Ё', 'ж', 'Ж', 'з', 'З', 'и', 'И', 'й', 'Й', 'к', 'К', 'л', 'Л', 'м', 'М', 'н', 'Н', 'о', 'О', 'п', 'П', 'р', 'Р', 'с', 'С', 'т', 'Т', 'у', 'У', 'ф', 'Ф', 'х', 'Х', 'ц', 'Ц', 'ч', 'Ч', 'ш', 'Ш', 'щ', 'Щ', 'ъ' => '', 'Ъ' => '', 'ы', 'Ы', 'ь' => '', 'Ь' => '', 'э', 'Э', 'ю', 'Ю', 'я', 'Я',
			' ', '&',
		],
		[
			'A', 'a', 'A', 'a', 'A', 'a', 'A', 'a', 'Ae', 'ae', 'AE', 'ae', 'A', 'a',
			'b', 'B',
			'c', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'C',
			'd', 'D', 'd', 'D', 'd', 'D', 'dh', 'Dh',
			'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E',
			'f', 'F', 'f', 'F',
			'g', 'G', 'g', 'G', 'g', 'G', 'g', 'G',
			'h', 'H', 'h', 'H',
			'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'j',
			'J',
			'k', 'K',
			'l', 'L', 'l', 'L', 'l', 'L', 'l', 'L',
			'm', 'M',
			'n', 'N', 'n', 'N', 'n', 'N', 'n', 'N',
			'o', 'O', 'o', 'O', 'o', 'O', 'o', 'O', 'o', 'O', 'oe', 'OE', 'o', 'O', 'o', 'O', 'oe', 'OE',
			'p', 'P',
			'r', 'R', 'r', 'R', 'r', 'R',
			's', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'S', 'SS',
			't', 'T', 't', 'T', 't', 'T', 't', 'T', 't', 'T',
			'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'ue', 'UE',
			'w', 'W', 'w', 'W', 'w', 'W', 'w', 'W',
			'y', 'Y', 'y', 'Y', 'y', 'Y', 'y', 'Y',
			'z', 'Z', 'z', 'Z', 'z', 'Z',
			'th', 'Th', 'u', 'a', 'a', 'b', 'b', 'v', 'v', 'g', 'g', 'd', 'd', 'e', 'E', 'e', 'E', 'zh', 'zh', 'z', 'z', 'i', 'i', 'j', 'j', 'k', 'k', 'l', 'l', 'm', 'm', 'n', 'n', 'o', 'o', 'p', 'p', 'r', 'r', 's', 's', 't', 't', 'u', 'u', 'f', 'f', 'h', 'h', 'c', 'c', 'ch', 'ch', 'sh', 'sh', 'sch', 'sch', '', '', 'y', 'y', '', '', 'e', 'e', 'ju', 'ju', 'ja', 'ja',
			'_', 'and',
		], $string);
	//'','','','','','',''), $string);
	// Now replaces all other special character by nothing.
	return preg_replace('#[^a-z0-9\-\_\.]#i', '', $string);
}

/**
 * Get the string of a boolean
 *
 * @param bool $b The boolean
 * @return string The boolean's string
 */
function b(bool $b): string {
	return $b ? 'TRUE' : 'FALSE';
}

/**
 * Split a string by string in limited values
 *
 * @param string $delimiter The boundary string
 * @param string $string The input string
 * @param int $limit The limit of values exploded
 * @param mixed $default The default value to use if missing
 * @return array The exploded array with a defined limit of values.
 * @see explode()
 *
 * Split a string by string in a limited number of values.
 * The main difference with explode() is this function complete missing values with $default.
 * If you want $limit optional, use explode()
 */
function explodeList(string $delimiter, string $string, int $limit, mixed $default = null): array {
	return array_pad(explode($delimiter, $string, $limit), abs($limit), $default);
}

/**
 * Hash string with salt
 *
 * @return string
 *
 * Hash input string with salt (constant USER_SALT) using SHA512
 */
function hashString(string $value): string {
	//http://www.php.net/manual/en/faq.passwords.php
	$salt = defined('USER_SALT') ? USER_SALT : '1$@g&';
	
	return hash('sha512', $salt . $value . '7');
}

/**
 * Get the date as string
 *
 * @param string $datetime The datetime
 * @return string The date using 'dateFormat' translation key
 *
 * Date format is storing a date, not a specific moment, we don't care about timezone
 */
function sql2Time(string $datetime): string {
	return strtotime($datetime . ' GMT');
}

/**
 * Format the date as string
 * Date format is storing a date, not a specific moment, we don't care about timezone
 *
 * @param int|DateTime $time The UNIX timestamp
 * @param bool $utc Is the time UTC
 * @return string The date using 'dateFormat' translation key
 */
function d(int|DateTime $time = TIME, bool $utc = false): string {
	return df('dateFormat', $time, $utc ? false : null);
}

/**
 * Format the date time as string
 * Datetime format is storing a specific moment, we care about timezone
 *
 * @param int|DateTime $time The UNIX timestamp
 * @param bool $utc Is the time UTC
 * @return string The date using 'datetimeFormat' translation key
 */
function dt(int|DateTime $time = TIME, bool $utc = false): string {
	return df('datetimeFormat', $time, $utc ? false : null);
}

/**
 * Format the date time as string
 *  Datetime format is storing a specific moment, we care about timezone
 *
 * @param string $format The format to use
 * @param int|DateTime|null $time The UNIX timestamp
 * @param string|false|null $timeZone Timezone to use. False for UTC, Null for default or a string to specify the one to use
 * @return string The date formatted using $format
 */
function df(string $format, int|DateTime|null $time = TIME, DateTimeZone|string|false|null $timeZone = null): string {
	if( $time === null ) {
		return '';
	}
	if( $timeZone === false ) {
		$timeZone = 'UTC';
	}
	// Calculating some delay, we want 00:00 and not null
	try {
		$date = is_object($time) ? $time : new DateTime('@' . $time, timezone($timeZone));
	} catch( Exception $exception ) {
		throw new RuntimeException($exception->getMessage(), $exception->getCode(), $exception);
	}
	
	return $date->format(t($format));
}

/**
 * @throws Exception
 */
function timeZone(string|DateTimeZone|null $timeZone): ?DateTimeZone {
	if( is_string($timeZone) ) {
		return new DateTimeZone($timeZone);
	}
	
	return $timeZone;
}

/**
 * Convert date to time
 * Allow any format of strtotime() to be converted to time, if time passed, it just returns it.
 *
 * @param int|string|DateTime $date The date or UNIX timestamp
 * @return int The UNIX timestamp
 */
function dateToTime(int|string|DateTime $date): int {
	if( is_numeric($date) ) {
		return $date;
	}
	if( $date instanceof DateTime ) {
		return $date->getTimestamp();
	}
	
	return strtotime($date . ' GMT');
}

/**
 * Create time format regex from date() format
 *
 */
function timeFormatToRegex(string $format): string {
	return '#^' . str_replace(['H', 'M'], ['([0-1][0-9]|2[0-3])', '([0-5][0-9])'], $format) . '$#';
}

/**
 * Parse time from string to time array
 *
 * @param string $time Parsed time
 * @param string $format Format to use
 * @throws Exception
 */
function parseTime(string $time, string $format = SYSTEM_TIME_FORMAT): array {
	$matches = null;
	if( !preg_match(timeFormatToRegex($format), $time, $matches) ) {
		throw new Exception('invalidTimeParameter');
	}
	/** @noinspection PhpParamsInspection */
	array_shift($matches);
	
	return $matches;
}

/**
 * Get the date as string in SQL format
 *  Date format is storing a date, not a specific moment, we don't care about timezone
 *
 * @param int|DateTime|string|null $time The UNIX timestamp.
 * @return string The date using sql format
 */
function sqlDate(int|DateTime|string|null $time = TIME): string {
	return sqlDatetime($time, 'Y-m-d');
}

/**
 * Get the date time as string in SQL format
 * Datetime format is storing a specific moment, we care about timezone
 *
 * @param int|DateTime|string|null $time The UNIX timestamp.
 * @return string The date using sql format
 */
function sqlDatetime(int|DateTime|string|null $time = TIME, string $format = 'Y-m-d H:i:s'): string {
	if( $time === null ) {
		$time = TIME;
	}
	if( $time instanceof DateTime ) {
		return $time->format($format);
	}
	if( is_string($time) ) {
		if( ctype_digit($time) ) {
			$time = intval($time);
		} else {
			// Already a formatted string
			return $time;
		}
	}
	
	return gmdate($format, $time);
}

/**
 * Get the client public IP
 *
 * @return string The ip of the client
 */
function clientIp(): string {
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
function userId(): int|string {
	$user = SecurityService::get()->getActiveUser();
	
	return $user ? $user->id() : 0;
}

/**
 * Generate a random string
 *
 * @param int $length The length of the output string
 * @param string $keyspace A string of all possible characters to select from
 */
function generateRandomString(int $length = 64, string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'): string {
	if( $length < 1 ) {
		throw new RangeException('Length must be a positive integer');
	}
	$string = '';
	$max = mb_strlen($keyspace, '8bit') - 1;
	for( $i = 0; $i < $length; ++$i ) {
		$string .= $keyspace[mt_rand(0, $max)];
	}
	
	return $string;
}

/**
 * Calculate the day timestamp using the given integer
 * Return the timestamp of the current day of $time according to the midnight hour.
 *
 * @param int|null $time The time to get the day time. Default value is current timestamp.
 * @param bool $gmt Is the time GMT
 */
function dayTime(?int $time = null, bool $gmt = true): int {
	$time ??= time();
	
	return $time - $time % 86400 - ($gmt ? date('Z') : 0);
}

/**
 * Add zero to the input number
 *
 * @param int $number The number
 * @param int $length The length to add zero
 */
function leadZero(int $number, int $length = 2): string {
	return sprintf('%0' . $length . 'd', $number);
}

/**
 * Format duration to closest unit
 *
 * @param int $duration Duration in seconds
 */
function formatDuration_Shortest(int $duration): string {
	$formats = ['days' => 86400, 'hours' => 3600, 'minutes' => 60];
	foreach( $formats as $unit => $time ) {
		$r = $duration / $time;
		if( $r >= 1 ) {
			break;
		}
	}
	
	return t($unit . '_short', 'global', [intval($r)]);
}

/**
 * Count intersect key in given arrays
 *
 */
function count_intersect_keys(array $array1, array $array2): int {
	return count(array_intersect_key($array1, $array2));
}

/**
 * Get the mime type of the given file path
 *
 */
function getMimeType(string $filePath): string {
	if( function_exists('finfo_open') ) {
		return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filePath);
	}
	
	return mime_content_type($filePath);
}

/**
 * Ensure path availability as folder
 *
 */
function checkDir(string $filePath): bool {
	return is_dir($filePath) || mkdir($filePath, 0772, true);
}

/**
 * Insert $value in $array at $position
 *
 * @set array_splice()
 */
function array_insert(array &$array, int $position, mixed $value): void {
	array_splice($array, $position, 0, $value);
}

/**
 * Add values from an array to another
 *
 */
function array_add(array &$array, array $other): void {
	$array = array_merge($array, $other);
}

/**
 * Filter $array entries by $keys
 *
 */
function array_filter_by_keys(array $array, array $keys): array {
	$r = [];
	foreach( $keys as $key ) {
		if( array_key_exists($key, $array) ) {
			$r[$key] = $array[$key];
		}
	}
	
	return $r;
}

/**
 * Get the index of $key in $array
 *
 */
function array_index(array $array, string|int $key): int {
	return array_search($key, array_keys($array));
}

/**
 * Get the last value of $array
 *
 * @return mixed|false
 */
function array_last(array $array): mixed {
	// Copy of array, the pointer is not moved
	return end($array);
}

/**
 * Get value of $array at $index or $default if not found
 *
 */
function array_get(array $array, int $index, mixed $default = null): mixed {
	$array = array_values($array);
	
	return $array[$index] ?? $default;
}

/**
 * Apply a user supplied function to every member of an array
 *
 * @param array $array The input array.
 * @param callable $callback Typically, callback takes on two parameters. The array parameter's value being the first, and the key/index second.
 * @param mixed $userdata If the optional userdata parameter is supplied, it will be passed as the third parameter to the callback.
 * @param string $success TRUE on success or FALSE on failure.
 * @return array The resulting array
 */
function array_apply(array $array, callable $callback, mixed $userdata = null, mixed &$success = null): array {
	$success = array_walk($array, $callback, $userdata);
	
	return $array;
}

/**
 * Concat key and value in an array with a glue
 *
 * @param string[] $array
 */
function array_peer(array $array, string $peerGlue = ': '): array {
	return array_apply($array, function (&$v, $k) use ($peerGlue) {
		$v = $k . $peerGlue . $v;
	});
}

/**
 * Make a string's first-only character uppercase
 *
 */
function str_ucfirst(string $value): string {
	return ucfirst(strtolower($value));
}

/**
 * Uppercase the first-only character of each word in a string
 *
 */
function str_ucwords(string $value): string {
	return ucwords(strtolower($value));
}

/**
 * Get the first char of a string
 *
 */
function str_first(string $value): string {
	return $value[0];
}

/**
 * Get the last char of a string
 *
 */
function str_last(string $value): string {
	return substr($value, -1);
}

/**
 * Reverse values
 *
 */
function reverse_values(mixed &$val1, mixed &$val2): void {
	$tmp = $val1;
	$val1 = $val2;
	$val2 = $tmp;
}

/**
 * Check value in between min and max
 *
 */
function between(int $value, int $min, int $max): bool {
	return $min <= $value && $value <= $max;
}

/**
 * Delete a HTTP cookie
 *
 * @param string $name The name of the cookie to delete
 * @return bool True if cookie was deleted, false if not found
 */
function deleteCookie(string $name): bool {
	if( !isset($_COOKIE[$name]) ) {
		return false;
	}
	unset($_COOKIE[$name]);
	setcookie($name, '', 1, '/');
	
	return true;
}

/**
 * Start a secured PHP Session and initialize Orpheus
 *
 * @param int $type The type flag of the session
 */
function startSession(int $type = SESSION_WITH_COOKIE): void {
	/**
	 * By default, browsers share cookies across subdomains
	 * So, we change the session name (also the cookie name) according to host
	 * and specify host as .domain.com (prefixed by a dot).
	 */
	if( session_status() !== PHP_SESSION_NONE ) {
		// Already started
		return;
	}
	
	if( matchBits($type, SESSION_WITH_COOKIE) ) {
		defifn('SESSION_COOKIE_LIFETIME', 86400 * 7);
		defifn('SESSION_COOKIE_PATH', '/');
		// Set session cookie parameters, HTTPS session is only HTTPS
		// Never set the domain, it will apply to subdomains
		// domain.com shares cookies with all subdomains... HTTP made me cry
		session_set_cookie_params(SESSION_COOKIE_LIFETIME, SESSION_COOKIE_PATH, SESSION_SHARE_ACROSS_SUBDOMAIN ? '' : '.' . HOST, HTTPS, true);
	}
	
	// Make cookie domain-dependant
	session_name('PHPSESSID' . (SESSION_SHARE_ACROSS_SUBDOMAIN ? '' : sprintf('%u', crc32(HOST))));
	
	// PHP is unable to manage exception thrown during session_start()
	$GLOBALS['ERROR_ACTION'] = ERROR_DISPLAY_RAW;
	session_start();
	$GLOBALS['ERROR_ACTION'] = ERROR_THROW_EXCEPTION;
	
	$initSession = function () {
		/** @noinspection PhpArrayWriteIsNotUsedInspection */
		$_SESSION = ['ORPHEUS' => [
			'LAST_REGENERATE_ID' => TIME,
			'CLIENT_IP'         => clientIp(),
			'SESSION_VERSION'   => defined('SESSION_VERSION') ? SESSION_VERSION : 1,
		]];
	};
	if( !isset($_SESSION['ORPHEUS']) ) {
		$initSession();
	} elseif( defined('SESSION_VERSION') && (!isset($_SESSION['ORPHEUS']['SESSION_VERSION']) || floor($_SESSION['ORPHEUS']['SESSION_VERSION']) != floor(SESSION_VERSION)) ) {
		// Outdated session version
		$initSession();
		throw new UserException('outdatedSession');
	} elseif( !isset($_SESSION['ORPHEUS']['CLIENT_IP']) ) {
		// Old session (Will be removed)
		$_SESSION['ORPHEUS']['CLIENT_IP'] = clientIp();
	} else if( Config::get('session_moved_allow', false) && $_SESSION['ORPHEUS']['CLIENT_IP'] !== clientIp() ) {
		// Hack Attempt - Session stolen
		// It will return hack attempt even if user is using a VPN
		// Allow 'reset', 'home', 'exception' / Default is 'reset'
		//		$movedAction = Config::get('moved_session_action', 'home');// No more supporting this feature, any hack should be rejected to error page
		// reset in all cases
		$initSession();
		throw new UserException('movedSession');
	}
}

/**
 * Find whether the given variable is a closure
 *
 * @return bool True if $v is a closure
 */
function is_closure(mixed $value): bool {
	return $value instanceof Closure;
}

/**
 * Test the given variable is an exception
 *
 * @return bool True if $v is an Exception
 */
function is_exception(mixed $exception): bool {
	return $exception instanceof Throwable;
}

/**
 * Get microsecond as UNIX format
 */
function ms(?int $precision = null): string {
	return $precision !== null ? number_format(microtime(true), $precision, '.', '') : round(microtime(true) * 1000);
}

/**
 * Convert human size to byte unit
 * Some programs, as of PHP, does not respect binary & decimals unit multiples
 * 100kB = 1.000 bytes / 100KiB = 1.024 bytes
 * In case of PHP ini file, 56MB is for PHP 56 * 1024 * 1024 bytes
 * Source: https://en.wikipedia.org/wiki/Byte#Unit_symbol
 *
 * @param string $size The human size to parse
 * @param bool $forceBinaryStep For to use binary step even if using decimal unit
 * @return int The byte size
 * @throws Exception
 */
function parseHumanSize(string $size, bool $forceBinaryStep = false): int {
	if( !preg_match('#^([0-9]+)\s*([a-z]*)$#', strtolower(trim($size)), $matches) ) {
		throw new Exception(sprintf('Invalid size "%s"', $size));
	}
	[, $value, $unit] = $matches;
	$decimalUnits = ['kb', 'mb', 'gb', 'tb', 'pb', 'eb', 'zb', 'yb'];
	$binaryUnits = ['kib', 'mib', 'gib', 'tib', 'pib', 'eib', 'zib', 'yib'];
	$step = 1024;
	if( $unit ) {
		$unitIndex = array_search($unit, $decimalUnits, true);
		if( $unitIndex === false ) {
			$unitIndex = array_search($unit, $binaryUnits, true);
		} elseif( !$forceBinaryStep ) {
			$step = 1000;
		}
		if( $unitIndex === false ) {
			throw new Exception(sprintf('Unknown unit "%s" in "%s"', $unit, $size));
		}
		$value *= pow($step, $unitIndex + 1);
	}
	
	return $value;
}

/**
 * Convert human size to byte unit
 * You can combine $step and decimal unit as you want
 * Source: https://en.wikipedia.org/wiki/Byte#Unit_symbol
 *
 * @param string $value The byte size to format
 * @param int $step The step between units
 * @param bool $useDecimalUnit The unit to use (decimal or binary)
 * @param int $allowMax Max value allow in one unit. e.g. with 10.000, you keep a unit until 9.999
 * @param string $format The format to use with sprintf(), first string is the value and the second one is the unit.
 * @return string The formatted size
 */
function formatHumanSize(string $value, int $step = 1000, bool $useDecimalUnit = true, int $allowMax = 10000, string $format = '%s%s'): string {
	$units = $useDecimalUnit ? ['kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'] : ['KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];
	$valueUnit = null;
	foreach( $units as $unit ) {
		if( $value < $allowMax ) {
			break;
		}
		$value /= $step;
		$valueUnit = $unit;
	}
	
	return sprintf($format, ($value <= 999 && is_float($value) ? formatNumber($value, 2) : formatNumber($value)), $valueUnit);
}
