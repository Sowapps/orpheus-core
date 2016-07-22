<?php
/**
 * The validators
 * 
 * PHP File containing all basic validators for a website.
 */

/** 
 * Check if the input is an email address.
 * 
 * @param $email The email address to check.
 * @return True if $email si a valid email address.
 */
function is_email($email) {
	return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/** 
 * Check if the input is a name.
 * 
 * @param $name The name to check.
 * @param $charnb_max The maximum length of the given name. Default value is 50.
 * @param $charnb_min The minimum length of the given name. Default value is 3.
 * @return True if $name si a name.
 * @sa is_personalname()
 * 
 * The name is a slug with no special characters.
 */
function is_name($name, $charnb_max=50, $charnb_min=3) {
	return preg_match('#^[a-z0-9\-\_]{'.$charnb_min.','.$charnb_max.'}$#i', $name);
}

/** 
 * Check if the input is a personal name.
 * 
 * @param $name The name to check.
 * @param $charnb_max The maximum length of the given name. Default value is 50.
 * @param $charnb_min The minimum length of the given name. Default value is 3.
 * @return True if $name si a name.
 * @sa is_name()
 * 
 * The name can not contain programming characters like control characters, '<', '>' or '='...
 */
function is_personalname($name, $charnb_max=50, $charnb_min=3) {
	// \'
	return preg_match('#^[^\^\<\>\*\+\(\)\[\]\{\}\"\~\&\=\:\;\`\|\#\@\%\/\\\\[:cntrl:]]{'.$charnb_min.','.$charnb_max.'}$#i', $name);
}

/** 
 * Check if the input is an ID Number.
 * 
 * @param $Number The number to check.
 * @return True if $Number si a valid integer.
 * 
 * The ID number is an integer.
 */
function is_ID($Number) {
	$Number = "$Number";
	return is_scalar($Number) && ctype_digit($Number) && $Number > 0;
}

define('DATE_FORMAT_LOCALE',	0);
define('DATE_FORMAT_SQL',		1);
define('DATE_FORMAT_GNU',		2);

/** 
 * Check if the input is a date.
 * 
 * @param $date string The date to check.
 * @param $withTime boolean True to use datetime format, optional. Default value is false.
 * @param $time integer The output timestamp of the data, optional.
 * @param $country string The country to use the date format, optional. Default and unique value is FR, not used.
 * @return True if $date si a valid date.
 *
 * The date have to be well formatted and valid.
 * The FR date format is DD/MM/YYYY and time format is HH:MM:SS
 * Allow 01/01/1970, 01/01/1970 12:10:30, 01/01/1970 12:10
 * Fill missing informations with 0.
 */
function is_date($date, $withTime=false, &$time=false, $format=null) {
	/* @var DateTime $dateTime */
// 	debug('is_date('.$date.', '.b($withTime).', '.$time.', '.$country.')');
	if( !$format ) {
		$time = strtotime($date);
		if( $time !== false ) {
			return true;
		}
		$format = DATE_FORMAT_LOCALE;
	}
	if( $format === DATE_FORMAT_SQL ) {
		$dateTime = DateTime::createFromFormat($withTime ? 'd/m/Y H:i:s' : 'd/m/Y|', $date);
	} else
	if( $format === DATE_FORMAT_GNU ) {
		$dateTime = DateTime::createFromFormat($withTime ? 'Y-m-d H:i:s' : 'Y-m-d|', $date);
	} else {
		// TODO: Use cookie timezone
		$dateTime = DateTime::createFromFormat(t($withTime ? 'datetimeFromFormat' : 'dateFromFormat'), $date);
	}
// 	debug('$dateTime', $dateTime);
// 	die();
	if( $dateTime ) {
		$time = $dateTime->getTimestamp();
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
function is_time($time, &$matches=null) {
	$format	= hasTranslation('timeFormat') ? t('timeFormat') : '%H:%M';
	//(?:[0-1][0-9]|2[0-3]):[0-5][0-9]
	return preg_match(timeFormatToRegex($format), $time, $matches);
}

/** 
 * Check if the input is an url.
 * 
 * @param $Url The url to check.
 * @param $protocol Not used yet. Default to SCHEME constant, not used.
 * @return True if $Url si a valid url.
 */
function is_url($Url, $protocol=null) {
	return filter_var($Url, FILTER_VALIDATE_URL);
}

/** 
 * Check if the input is an ip address.
 * 
 * @param $ip The url to check.
 * @param $flags The flags for the check.
 * @return True if $ip si a valid ip address.
 * @sa filter_var()
 */
function is_ip($ip, $flags=null) {
	return filter_var($ip, FILTER_VALIDATE_IP, $flags);
}

/** 
 * Check if the input is a phone number.
 * 
 * @param $number The phone number to check.
 * @param $country The country to use to validate the phone number, default is FR, this is the only possible value
 * @return True if $number si a valid phone number.
 * 
 * It can only validate french phone number.
 * The separator can be '.', ' ' or '-', it can be ommitted.
 * e.g: +336.12.34.56.78, 01-12-34-56-78
 */
function is_phone_number($number, $country='FR') {
	$number	= str_replace(array('.', ' ', '-'), '', $number);
	return preg_match("#^(?:\+[0-9]{1,3}|0)[0-9]{9}$#", $number);
}