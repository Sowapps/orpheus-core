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
function is_email(string $email): bool {
	return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Check the $variable could be converted into a string
 *
 */
function is_string_convertible(mixed $variable): bool {
	return !is_array($variable) && (
			(!is_object($variable) && settype($variable, 'string') !== false) ||
			(is_object($variable) && method_exists($variable, '__toString'))
		);
}

/**
 * Check if the input is an ID Number.
 *
 * @param mixed $id The number to check
 * @return boolean True if $number si a valid integer
 *
 * The ID number is an integer.
 */
function is_id(mixed $id): bool {
	return is_scalar($id) && ctype_digit($id) && intval($id) > 0;
}

const DATE_FORMAT_LOCALE = 0;
const DATE_FORMAT_GNU = 1;

/**
 * Check if the input is a date.
 *
 * @param string $date The date to check.
 * @param boolean $withTime True to use datetime format, optional. Default value is false.
 * @param DateTime|null $dateTime The output timestamp of the data, optional.
 * @param int $format The date format to check, see constants DATE_FORMAT_*
 * @return boolean True if $date si a valid date.
 *
 * The date have to be well formatted and valid.
 * The FR date format is DD/MM/YYYY and time format is HH:MM:SS
 * Allow 01/01/1970, 01/01/1970 12:10:30, 01/01/1970 12:10
 * Fill missing information with 0.
 */
function is_date(string $date, bool $withTime = false, ?DateTime &$dateTime = null, int $format = DATE_FORMAT_LOCALE): bool {
	// SQL USES GNU
	if( $format === DATE_FORMAT_GNU ) {
		$dateTime = DateTime::createFromFormat($withTime ? 'Y-m-d H:i:s' : 'Y-m-d|', $date);
	} else {
		$dateTime = DateTime::createFromFormat(t($withTime ? 'datetimeFromFormat' : 'dateFromFormat'), $date);
	}
	
	return !!$dateTime;
}

/**
 * Check $time is a real time representation.
 * Could use global translation "timeFormat" to check this is a time
 * e.g. Basically validate 12:50
 *
 */
function is_time(string $time, ?array &$matches = null): bool {
	$format = tn('timeFormat') ?? 'H:M';
	
	return preg_match(timeFormatToRegex($format), $time, $matches);
}

/**
 * Check if the input is an url.
 *
 * @param string $url The url to check.
 * @return boolean True if $url si a valid url.
 */
function is_url(string $url): bool {
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
function is_ip(string $ip, int $flags = FILTER_DEFAULT): bool {
	return filter_var($ip, FILTER_VALIDATE_IP, $flags);
}
