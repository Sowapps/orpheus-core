<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Formatter;

interface FileFormatable {
	
	/**
	 * Format data to contents
	 *
	 * @param $data
	 * @param bool $pretty
	 * @return string
	 */
	function format($data, bool $pretty = false): string;
	
	/**
	 * Parse file to data
	 *
	 * @param string $path
	 * @return mixed
	 */
	function parse(string $path): mixed;
	
}
