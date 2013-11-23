<?php

namespace Common;

class Collection
	extends \ArrayIterator
{

	// Stack Modification

	/**
	 * Returns the first item
	 * @return mixed
	 */
	public function first()
	{
		$this->rewind();
		return $this->current();
	}

	/**
	 * Returns the last item
	 * @return mixed
	 */
	public function last()
	{
		$this->seek($this->count() - 1);
		return $this->current();
	}

}