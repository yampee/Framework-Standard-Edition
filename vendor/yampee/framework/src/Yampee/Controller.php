<?php

/*
 * Yampee Framework
 * Open source web development framework for PHP 5.
 *
 * @package Yampee Framework
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @link http://titouangalopin.com
 */

/**
 * Abstract class for controllers. Use the dependency injection container.
 */
abstract class Yampee_Controller extends Yampee_Di_ContainerAware
{
	/**
	 * Constructor
	 *
	 * @param Yampee_Di_Container $container
	 */
	final public function __construct(Yampee_Di_Container $container)
	{
		$this->container = $container;
	}

	/**
	 * @return Yampee_Http_Exception_NotFound
	 */
	public function createNotFoundException()
	{
		return new Yampee_Http_Exception_NotFound();
	}

	/**
	 * @return Yampee_Http_Exception_AccessDenied
	 */
	public function createAccessDeniedException()
	{
		return new Yampee_Http_Exception_AccessDenied();
	}

	/**
	 * Renders a template included in a response.
	 *
	 * @param string $name    The template name
	 * @param array  $context An array of parameters to pass to the template
	 *
	 * @return string The rendered template
	 */
	public function render($name, array $context = array())
	{
		return new Yampee_Http_Response($this->get('twig')->render($name, $context));
	}

	/**
	 * @param string $url
	 * @param int    $status
	 * @return Yampee_Http_RedirectResponse
	 */
	public function redirect($url, $status = 302)
	{
		return new Yampee_Http_RedirectResponse($url, $status);
	}

	/**
	 * @param string $route
	 * @param array  $parameters
	 * @return string
	 */
	public function generateUrl($route, $parameters = array())
	{
		return $this->container->get('router')->generate($route, $parameters);
	}

	/**
	 * @return Yampee_Routing_Router
	 */
	public function getRouter()
	{
		return $this->container->get('router');
	}

	/**
	 * @return Yampee_Db_Manager
	 */
	public function getDatabase()
	{
		return $this->container->get('database');
	}

	/**
	 * @return Yampee_Ed_Dispatcher
	 */
	public function getEventDispatcher()
	{
		return $this->container->get('event_dispatcher');
	}

	/**
	 * @return Yampee_Log_Logger
	 */
	public function getLogger()
	{
		return $this->container->get('logger');
	}

	/**
	 * @return Yampee_Translator_Interface
	 */
	public function getTranslator()
	{
		return $this->container->get('translator');
	}
}