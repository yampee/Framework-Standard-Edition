<?php

/*
 * Yampee Components
 * Open source web development components for PHP 5.
 *
 * @package Yampee Components
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @link http://titouangalopin.com
 */

/**
 * Cache manager.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class Yampee_Cache_Manager
{
	/**
	 * @var Yampee_Cache_Storage_Interface
	 */
	protected $driver;

	/**
	 * Constructor
	 *
	 * @param Yampee_Cache_Storage_Interface $driver
	 */
	public function __construct(Yampee_Cache_Storage_Interface $driver)
	{
		$this->driver = $driver;
		$this->driver->open();
	}

	/**
	 * Destructor
	 */
	public function __destruct()
	{
		$this->driver->close();
	}

	/**
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		return $this->driver->get($key, $default);
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function has($key)
	{
		return $this->driver->has($key);
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @param int    $expire
	 * @return Yampee_Cache_Manager
	 */
	public function set($key, $value, $expire = 0)
	{
		$this->driver->set($key, $value, $expire);

		return $this;
	}
}