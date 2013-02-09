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
 * Class to benchmark the script and profile loading time.
 */
class Yampee_Benchmark
{
	/**
	 * @var array
	 */
	static protected $times;

	/**
	 * Start the benhmark.
	 */
	static public function start()
	{
		self::$times = array('start' => microtime(true));
	}

	/**
	 * Mark a position in the script.
	 *
	 * @param $name
	 */
	static public function markAs($name)
	{
		if (empty(self::$times)) {
			self::start();
		}

		self::$times[$name] = (microtime(true) - self::$times['start']) * 1000;
	}

	/**
	 * @return array
	 */
	static public function getAll()
	{
		return self::$times;
	}
}