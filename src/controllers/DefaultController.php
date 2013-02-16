<?php

class DefaultController extends Yampee_Controller
{
	/**
	 * @Route('/', name='homepage')
	 * @Template()
	 * @HttpCache()
	 */
	public function indexAction()
	{
		return array('name' => 'Titouan');
	}
}