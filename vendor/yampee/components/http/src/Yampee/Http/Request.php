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
 * HTTP request
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class Yampee_Http_Request
{
	/**
	 * @var array $attributes
	 */
	private $attributes;

	/**
	 * @param array $attributes
	 */
	public function __construct($attributes = array())
	{
		$this->attributes = $attributes;
	}

	/**
	 * @return Yampee_Http_Request
	 */
	public static function createFromGlobals()
	{
		return new self(array_merge($_GET, $_POST, $_SERVER));
	}

	/**
	 * @param array $attributes
	 */
	public function setAttributes(array $attributes)
	{
		$this->attributes = $attributes;
	}

	/**
	 * @return array
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public function get($key)
	{
		$attributes = array();

		foreach($this->attributes as $attrKey => $attrValue) {
			$attributes[strtolower($attrKey)] = $attrValue;
		}

		return (isset($attributes[strtolower($key)])) ? $attributes[strtolower($key)] : false;
	}

	/**
	 * Get method
	 *
	 * @return string
	 */
	public function getMethod()
	{
		return $this->get('request_method');
	}

	/**
	 * Get the current client IP
	 *
	 * @return string
	 */
	public function getClientIp()
	{
		$clientIp = $this->get('http_client_ip');
		$forwardedIp = $this->get('http_x_forwarded_for');
		$remoteAddr = $this->get('remote_addr');

		if (! empty($clientIp)) {
			return $clientIp;
		}

		if (! empty($forwardedIp)) {
			return $forwardedIp;
		}

		return $remoteAddr;
	}
}