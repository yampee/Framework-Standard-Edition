<?php

/*
 * Yampee Framework
 * Open source web development framework for PHP 5.
 *
 * @package Yampee Framework
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @link http://titouangalopin.com
 */

class Yampee_Twig_Translation extends Twig_Extension
{
	public function __construct(Yampee_Translator_Interface $translator)
	{

	}

	public function getName()
	{
		return 'yampee_translation';
	}
}