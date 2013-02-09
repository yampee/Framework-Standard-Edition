<?php


/**
 * @Service(
 *      'test',
 *      arguments = [ '@container' ],
 *      tags = [
 *          { name = 'event.listener', event = 'kernel.loaded', method = 'kernelLoaded' },
 *          { name = 'event.listener', event = 'kernel.request', method = 'kernelRequest' },
 *          { name = 'event.listener', event = 'kernel.action', method = 'kernelAction' },
 *          { name = 'event.listener', event = 'kernel.response', method = 'kernelResponse' }
 *      ]
 * )
 */
class Service
{
	protected $container;

	public function __construct($container)
	{
		$this->container = $container;
	}

	public function kernelLoaded(Yampee_Di_Container $container)
	{
	}

	public function kernelRequest(Yampee_Http_Request $request)
	{
	}

	public function kernelAction(Yampee_Routing_Route $route)
	{
	}

	public function kernelResponse(Yampee_Http_Response $response)
	{
	}
}