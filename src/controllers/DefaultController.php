<?php

class DefaultController extends Yampee_Controller
{
	/**
	 * @Route('/', name='homepage')
	 * @Template()
	 */
	public function indexAction()
	{
		return array();
	}
}