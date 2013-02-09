<?php

/*
 * Yampee Framework
 * Open source web development framework for PHP 5.
 *
 * @package Yampee Framework
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @link http://titouangalopin.com
 */

class Yampee_Twig_Core extends Yampee_Twig_Extension
{
	public function getName()
	{
		return 'core';
	}

	public function getFunctions()
	{
		return array(
			'dump' => new Twig_Function_Function('var_dump')
		);
	}
}