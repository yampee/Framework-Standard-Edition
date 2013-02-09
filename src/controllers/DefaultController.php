<?php

class DefaultController extends Yampee_Controller
{
	/**
	 * @Route('/', name='homepage')
	 */
	public function indexAction()
	{
		return $this->render('index.html.twig');
	}
}