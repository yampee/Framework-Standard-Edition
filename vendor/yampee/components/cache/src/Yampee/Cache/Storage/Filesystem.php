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
 * Cache storage that use files
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class Yampee_Cache_Storage_Filesystem implements Yampee_Cache_Storage_Interface
{
	/**
	 * @var string
	 */
	protected $cacheFile;

	/**
	 * @var string
	 */
	protected $cache;

	/**
	 * Constructor
	 *
	 * @param string $cacheFile
	 */
	public function __construct($cacheFile)
	{
		$this->cacheFile = (string) $cacheFile;
		$this->cache = array();
	}

	/**
	 * @return Yampee_Cache_Storage_Filesystem
	 */
	public function open()
	{
		if (file_exists($this->cacheFile)) {
			$this->cache = unserialize(file_get_contents($this->cacheFile));
		}

		return $this;
	}

	/**
	 * @return Yampee_Cache_Storage_Filesystem
	 */
	public function close()
	{
		file_put_contents($this->cacheFile, serialize($this->cache));

		return $this;
	}

	/**
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		if (! $this->has($key)) {
			return $default;
		}

		return $this->cache[$key]['value'];
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function has($key)
	{
		if (isset($this->cache[$key])) {
			if ($this->cache[$key]['expire'] != 0 && $this->cache[$key]['expire'] < time()) {
				unset($this->cache[$key]);
				return false;
			} else {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @param int    $expire
	 * @return Yampee_Cache_Storage_Filesystem
	 */
	public function set($key, $value, $expire = 0)
	{
		$this->cache[$key] = array(
			'expire' => time() + $expire,
			'value' => $value
		);

		return $this;
	}
}