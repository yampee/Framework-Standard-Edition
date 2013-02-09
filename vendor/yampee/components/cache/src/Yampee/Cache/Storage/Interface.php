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
 * Interface for cache storages systems
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
interface Yampee_Cache_Storage_Interface
{
	/**
	 * Open cache access
	 *
	 * @return mixed
	 */
	public function open();

	/**
	 * Close cache access
	 *
	 * @return mixed
	 */
	public function close();

	/**
	 * Read from cache
	 *
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 */
	public function get($key, $default = null);

	/**
	 * Check if the given key exists in cache
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function has($key);

	/**
	 * Store an element in cache
	 *
	 * @param string $key
	 * @param mixed  $value
	 * @param int    $expire
	 * @return mixed
	 */
	public function set($key, $value, $expire = 0);
}