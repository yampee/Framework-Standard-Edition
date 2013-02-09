<?php

/*
 * Yampee Framework
 * Open source web development framework for PHP 5.
 *
 * @package Yampee Framework
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @link http://titouangalopin.com
 */

/**
 * Configuration object storage
 */
class Yampee_Config extends ArrayObject
{
	/**
	 * @param $index
	 * @return mixed
	 */
	public function get($index)
	{
		return $this->offsetGet($index);
	}

	/**
	 * @param $index
	 * @param $newval
	 * @return Yampee_Config
	 */
	public function set($index, $newval)
	{
		$this->offsetSet($index, $newval);
		return $this;
	}

	/**
	 * @param $index
	 * @param $newval
	 * @return Yampee_Config
	 */
	public function add($index, $newval = null)
	{
		if($newval !== null) {
			if(! $this->has($index)) {
				$this->set($index, array());
			}

			$array = $this->get($index);
			$array[] = $newval;

			$this->offsetSet($index, $array);
		} else {
			$array = $this->getArrayCopy();
			$array[] = $newval;

			$this->exchangeArray($array);
		}

		return $this;
	}

	/**
	 * @param $index
	 * @return Yampee_Config
	 */
	public function remove($index)
	{
		$this->offsetUnset($index);
		return $this;
	}

	/**
	 * @param $index
	 * @return bool
	 */
	public function has($index)
	{
		return $this->offsetExists($index);
	}

	/**
	 * @param array $array
	 * @return Yampee_Config
	 */
	public function merge(array $array)
	{
		$this->exchangeArray(array_merge($this->getArrayCopy(), $array));
		return $this;
	}

	/**
	 * Convert collection to 2D collection with dot notation keys
	 *
	 * @return Yampee_Config
	 */
	public function compile()
	{
		$iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($this->getArrayCopy()));
		$result = array();

		foreach($iterator as $leafValue) {
			$keys = array();

			foreach (range(0, $iterator->getDepth()) as $depth) {
				if (is_numeric($iterator->getSubIterator($depth)->key())) {
					break;
				}

				$keys[] = $iterator->getSubIterator($depth)->key();
			}

			$result[implode('.', $keys)] = $leafValue;
		}

		$this->exchangeArray($result);

		return $this;
	}
}