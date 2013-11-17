<?php

namespace Common;

class Collection
	implements \ArrayAccess, \Iterator
{
	private $collection;

	private $position = 0;

	public function __construct(array $collection = []) {
		$this->collection = $collection;
	}

	// Iterator

	public function current() {
		return $this->collection[$this->position];
	}

	public function key() {
		return $this->position;
	}

	public function next() {
		++$this->position;
	}

	public function rewind() {
		$this->position = 0;
	}

	public function valid() {
		return isset($this->collection[$this->position]);
	}

	// ArrayAccess
	public function offsetExists($offset)
	{
		return isset($this->collection[$offset]);
	}

	public function offsetGet($offset)
	{
		return isset($this->collection[$offset]) ? $this->collection[$offset] : null;
	}

	public function offsetSet($offset, $value)
	{
        if (is_null($offset)) {
            $this->collection[] = $value;
        } else {
            $this->collection[$offset] = $value;
        }		
	}

	public function offsetUnset($offset) {
		unset($this->collection[$offset]);
	}

	// Array Modifiers
	
	public function first()
	{
		$this->rewind();
		return $this->current();
	}

}