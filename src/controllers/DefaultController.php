<?php

class DefaultController extends Yampee_Controller
{
	/**
	 * @Route('/', name='homepage')
	 * @Template()
	 * @HttpCache()
	 * @Cache(expire = 10)
	 */
	public function indexAction()
	{
		return array('name' => 'Titouan');
	}
}