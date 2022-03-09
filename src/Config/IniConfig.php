<?php
/**
 * IniConfig
*/

namespace Orpheus\Config;

/**
 * The ini config class
 *
 * This class uses ini files to get configuration.
 */
class IniConfig extends Config {

	/**
	 * Extension for this config files
	 *
	 * @var string
	 */
	protected static $extension = 'ini';

	/**
	 * Parse configuration from given source.
	 *
	 * @param string $path The path to the config file
	 * @return mixed The loaded configuration array
	 *
	 * If an identifier, loads a configuration from a .ini file in CONFIG_FOLDER.
	 * Else $source is a full path to the ini configuration file.
	 */
	public static function parse($path) {
		return parse_ini_file($path, true);
	}
}
