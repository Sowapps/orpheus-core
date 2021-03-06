<?php
/**
 * The validators
 *
 * PHP File containing all basic validators for a website.
 */

/**
 * Check if the input is an email address.
 *
 * @param string $email The email address to check.
 * @return boolean True if $email si a valid email address.
 */
function is_email($email) {
	return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Check if the input is a name.
 *
 * @param string $name The name to check.
 * @param int $charnb_max The maximum length of the given name. Default value is 50.
 * @param int $charnb_min The minimum length of the given name. Default value is 3.
 * @return boolean True if $name si a name.
 * @sa is_personalname()
 *
 * The name is a slug with no special characters.
 */
function is_name($name, $charnb_max = 50, $charnb_min = 3) {
	return preg_match('#^[a-z0-9\-\_]{' . $charnb_min . ',' . $charnb_max . '}$#i', $name);
}

/**
 * Check if the input is a personal name.
 *
 * @param string $name The name to check.
 * @param int $charnb_max The maximum length of the given name. Default value is 50.
 * @param int $charnb_min The minimum length of the given name. Default value is 3.
 * @return boolean True if $name si a name.
 * @sa is_name()
 *
 * The name can not contain programming characters like control characters, '<', '>' or '='...
 */
function is_personalname($name, $charnb_max = 50, $charnb_min = 3) {
	return preg_match('#^[^\^\<\>\*\+\(\)\[\]\{\}\"\~\&\=\:\;\`\|\#\@\%\/\\\\[:cntrl:]]{' . $charnb_min . ',' . $charnb_max . '}$#i', $name);
}

/**
 * Check the $variable could be converted into a string
 *
 * @param $number
 * @return bool
 */
function is_string_convertible($variable) {
	return !is_array($variable) && (
			(!is_object($variable) && settype($variable, 'string') !== false) ||
			(is_object($variable) && method_exists($variable, '__toString'))
		);
}

/**
 * Check if the input is an ID Number.
 *
 * @param mixed $number The number to check.
 * @return boolean True if $number si a valid integer.
 *
 * The ID number is an integer.
 */
function is_ID($number) {
	if( !is_string_convertible($number) ) {
		return false;
	}
	$number = "$number";
	return is_scalar($number) && ctype_digit($number) && $number > 0;
}

define('DATE_FORMAT_LOCALE', 0);
define('DATE_FORMAT_GNU', 1);

/**
 * Check if the input is a date.
 *
 * @param string $date The date to check.
 * @param boolean $withTime True to use datetime format, optional. Default value is false.
 * @param DateTime $dateTime The output timestamp of the data, optional.
 * @param int $format The date format to check, see constants DATE_FORMAT_*
 * @return boolean True if $date si a valid date.
 *
 * The date have to be well formatted and valid.
 * The FR date format is DD/MM/YYYY and time format is HH:MM:SS
 * Allow 01/01/1970, 01/01/1970 12:10:30, 01/01/1970 12:10
 * Fill missing information with 0.
 */
function is_date($date, $withTime = false, &$dateTime = false, $format = null) {
	// SQL USES GNU
	if( $format === DATE_FORMAT_GNU ) {
		$dateTime = DateTime::createFromFormat($withTime ? 'Y-m-d H:i:s' : 'Y-m-d|', $date);
	} else {
		$dateTime = DateTime::createFromFormat(t($withTime ? 'datetimeFromFormat' : 'dateFromFormat'), $date);
	}
	return !!$dateTime;
}

/**
 * Check $time is a real time representation
 *
 * @param string $time
 * @param array $matches
 * @return boolean
 *
 * Could use global translation "timeFormat" to check this is a time
 * e.g Basically validate 12:50
 */
function is_time($time, &$matches = null) {
	$format = hasTranslation('timeFormat') ? t('timeFormat') : '%H:%M';
	return preg_match(timeFormatToRegex($format), $time, $matches);
}

/**
 * Check if the input is an url.
 *
 * @param string $url The url to check.
 * @param string $protocol Not used yet. Default to SCHEME constant, not used.
 * @return boolean True if $url si a valid url.
 */
function is_url($url, $protocol = null) {
	return filter_var($url, FILTER_VALIDATE_URL);
}

/**
 * Check if the input is an ip address.
 *
 * @param string $ip The url to check.
 * @param int $flags The flags for the check.
 * @return boolean True if $ip si a valid ip address.
 * @sa filter_var()
 */
function is_ip($ip, $flags = null) {
	return filter_var($ip, FILTER_VALIDATE_IP, $flags);
}

/**
 * Check if the input is a phone number.
 *
 * @param string $number The phone number to check.
 * @param string $country The country to use to validate the phone number, default is FR, this is the only possible value
 * @return boolean True if $number si a valid phone number.
 *
 * It can only validate french phone number.
 * The separator can be '.', ' ' or '-', it can be ommitted.
 * e.g: +336.12.34.56.78, 01-12-34-56-78
 */
function is_phone_number($number, $country = 'FR') {
	$number = str_replace(['.', ' ', '-'], '', $number);
	return preg_match("#^(?:\+[0-9]{1,3}|0)[0-9]{9}$#", $number);
}
