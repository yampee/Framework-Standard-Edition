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
 * Not Found HTTP exception
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class Yampee_Http_Exception_NotFound extends Yampee_Http_Exception_General
{
	/**
	 * Constructor.
	 *
	 * @param string    $message  The internal exception message
	 * @param Exception $previous The previous exception
	 * @param integer   $code     The internal exception code
	 */
	public function __construct($message = null, Exception $previous = null, $code = 0)
	{
		parent::__construct(404, $message, $previous, array(), $code);
	}
}
