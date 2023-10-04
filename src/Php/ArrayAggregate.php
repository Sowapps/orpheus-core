<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Php;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

abstract class ArrayAggregate implements IteratorAggregate, ArrayAccess, Countable {
	
	abstract public function &getArray(): array;
	
	public function getIterator(): Traversable {
		return new ArrayIterator($this->getArray());
	}
	
	public function offsetExists(mixed $offset): bool {
		$array = &$this->getArray();
		
		return isset($array[$offset]);
	}
	
	public function offsetGet(mixed $offset): mixed {
		return $this->getArray()[$offset];
	}
	
	public function offsetSet(mixed $offset, mixed $value): void {
		$array = &$this->getArray();
		if( $offset ) {
			$array[$offset] = $value;
		} else {
			$array[] = $value;
		}
	}
	
	public function offsetUnset(mixed $offset): void {
		$array = &$this->getArray();
		unset($array[$offset]);
	}
	
	public function count(): int {
		return count($this->getArray());
	}
	
}
