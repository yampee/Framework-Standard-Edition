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
 * Kernel
 */
class Yampee_Kernel
{
	/**
	 * Whether the kernel is in dev or not
	 *
	 * @var boolean
	 */
	protected $inDev;

	/**
	 * Cache manager
	 *
	 * @var Yampee_Cache_Manager
	 */
	protected $cache;

	/**
	 * Configuration
	 *
	 * @var Yampee_Config
	 */
	protected $config;

	/**
	 * Annotation reader
	 *
	 * @var Yampee_Annotations_Reader
	 */
	protected $annotationsReader;

	/**
	 * DI container
	 *
	 * @var Yampee_Di_Container
	 */
	protected $container;

	/**
	 * Yampee locator on the server
	 *
	 * @var Yampee_Locator
	 */
	protected $locator;

	/**
	 * Construct the Kernel
	 *
	 * @param bool $inDev
	 */
	public function __construct($inDev = false)
	{
		set_exception_handler(array('Yampee_ExceptionHandler', 'handle'));
		Yampee_ExceptionHandler::$inDev = $inDev;

		/*
		 * Error reporting
		 *
		 * Different environments will require different levels of error reporting.
		 * By default development will show errors but testing and production will hide them.
		 */
		if($inDev) {
			error_reporting(-1);
			ini_set('display_errors', 1);
		} else {
			error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_STRICT);
			ini_set('display_errors', 0);
		}

		/*
		 * Boot the kernel step by step
		 */
		$this->inDev = $inDev;

		Yampee_Benchmark::start();

		// Cache manager
		$this->cache = new Yampee_Cache_Manager(new Yampee_Cache_Storage_Filesystem(
			__APP__.'/app/cache/app.cache'
		));

		// Annotations reader
		$this->annotationsReader = new Yampee_Annotations_Reader();

		// Configuration
		$this->config = $this->loadConfig();
		Yampee_Benchmark::markAs('config.booted');

		// Container and services
		$this->container = $this->loadContainer();

		$this->container->setParameters($this->config->getArrayCopy());
		$this->container->setParameter('kernel.in_dev', $this->inDev);
		$this->container->setParameter('kernel.root_dir', __APP__);

		$this->container->set('cache', $this->cache);
		$this->container->set('config', $this->config);
		$this->container->set('annotations', $this->annotationsReader);
		$this->container->set('kernel', $this);

		$this->container->registerDefinitions($this->getCoreDefinitions());

		$this->container->build();

		Yampee_Benchmark::markAs('container.booted');

		if ($this->inDev) {
			$this->container->get('logger')->disable();
		}

		Yampee_ExceptionHandler::$twig = $this->container->get('twig');
		Yampee_ExceptionHandler::$logger = $this->container->get('logger');

		// Twig Extensions
		$this->loadTwigExtensions();

		// Event dispatcher
		$this->loadEventDispatcher();

		// Load the router and its routes
		$this->loadRouter();

		$this->container->get('logger')->debug('Kernel loaded');
		$this->container->get('event_dispatcher')->notify('kernel.loaded', array($this->container));
	}

	/**
	 * Handle the request, dispatch the action and return the response
	 *
	 * @param Yampee_Http_Request $request
	 * @return Yampee_Http_RedirectResponse|Yampee_Http_Response
	 * @throws LogicException
	 * @throws Yampee_Http_Exception_NotFound
	 */
	public function handle(Yampee_Http_Request $request)
	{
		$this->container->get('event_dispatcher')->notify('kernel.request', array($request));

		$this->container->get('logger')->debug('Request handled from '.$request->getClientIp());
		$this->container->set('request', $request);

		Yampee_ExceptionHandler::$clientIp = $request->getClientIp();

		Yampee_Benchmark::markAs('request.handled');

		$locator = $this->generateRootUrl($request);

		Yampee_ExceptionHandler::$url = $locator->getRequestUri();

		// Redirect without last "/"
		if (substr($locator->getRequestUri(), -1) == '/' && rtrim($locator->getRequestUri(), '/') != '') {
			$this->container->get('logger')->debug(
				'Redirect from '.$locator->getRequestUri().' to '.
					$locator->getRootUrl().rtrim($locator->getRequestUri(), '/')
			);

			return new Yampee_Http_RedirectResponse(
				$locator->getRootUrl().rtrim($locator->getRequestUri(), '/')
			);
		}

		// Dispatch the action
		$route = $this->getContainer()->get('router')->find($locator->getRequestUri());

		if(! $route) {
			throw new Yampee_Http_Exception_NotFound(sprintf(
				'No route found for GET %s', $locator->getRequestUri()
			));
		}

		Yampee_Benchmark::markAs('request.dispatched');

		// Call the action
		$this->container->get('logger')->debug('Action found: '.$route->getAction());

		$this->container->get('event_dispatcher')->notify('kernel.action', array($route));

		$action = explode('::', $route->getAction());

		if (! isset($action[1]) || count($action) > 2) {
			throw new LogicException(sprintf(
				'This action is invalid ("%s" given)', $route->getAction()
			));
		}

		$controller = new ReflectionClass($action[0]);
		$controller = $controller->newInstanceArgs(array($this->container));

		$action = new ReflectionMethod($controller, $action[1]);

		$arguments = array();
		$routeAttributes = $route->getAttributes();

		foreach($action->getParameters() as $parameter) {
			if(isset($routeAttributes[$parameter->getName()])) {
				$arguments[] = $this->cast($routeAttributes[$parameter->getName()]);
			}
		}

		$response = $action->invokeArgs($controller, $arguments);

		if(! $response instanceof Yampee_Http_Response) {
			throw new LogicException(sprintf(
				'Action %s must return a Yampee_Http_Response object (%s given).',
				$route->getAction(), gettype($response)
			));
		}

		// Read the HTTP cache annotation
		$this->annotationsReader->registerAnnotation(new Yampee_Http_Bridge_Annotation_Cache($response));
		$this->annotationsReader->readReflector($action);

		$this->container->get('logger')->debug('Response sent');

		Yampee_Benchmark::markAs('response.sent');
		$this->container->get('event_dispatcher')->notify('kernel.response', array($response));

		// If there is no problem, we clear logs and write a short description
		$this->container->get('logger')->clearCurrentScriptLog();
		$this->container->get('logger')->debug(sprintf(
			'%s "%s" handled from %s, calling %s',
			$request->getMethod(), $locator->getRequestUri(), $request->getClientIp(),
			$route->getAction()
		));

		// Finally send the response
		return $response;
	}

	/**
	 * @return Yampee_Config
	 */
	protected function loadConfig()
	{
		/*
		 * Boot the configuration
		 *
		 * Try to load it from cache if the production mode is enabled
		 */
		if ($this->inDev || ! $this->cache->has('config')) {
			$yaml = new Yampee_Yaml_Yaml();

			$config = new Yampee_Config($yaml->load(__APP__.'/app/config.yml'));
			$config->compile();

			$this->cache->set('config', $config);
		} else {
			$config = $this->cache->get('config');
		}

		return $config;
	}

	/**
	 * @return Yampee_Di_Container
	 */
	protected function loadContainer()
	{
		$container = new Yampee_Di_Container();

		$this->annotationsReader->registerAnnotation(new Yampee_Di_Bridge_Annotation_Service($container));

		/*
		 * Find all the services files (in src/services) and associate their classes.
		 */
		$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
			__APP__.'/src/services'
		));

		$classes = array();

		while($it->valid()) {
			if (! $it->isDot()) {
				$classes[] = trim(str_replace(
					array('\\', '/'), '_',
					$it->getSubPath().'_'.$it->getBasename('.php')
				), '_');
			}

			$it->next();
		}

		/*
		 * Read annotations on classes (if they exists) and register them in the container
		 * to build it then
		 */
		foreach ($classes as $class) {
			if (class_exists($class)) {
				$this->annotationsReader->readReflector(new ReflectionClass($class));
			}
		}

		return $container;
	}

	/**
	 * @return void
	 */
	protected function loadTwigExtensions()
	{
		$extensions = $this->container->findByTag('twig.extension');

		foreach ($extensions as $extension) {
			$this->container->get('twig')->addExtension($extension);
		}
	}

	/**
	 * @return void
	 */
	protected function loadEventDispatcher()
	{
		$listenersNames = $this->container->findNamesByTag('event.listener');

		foreach ($listenersNames as $listenerName) {
			$tags = $this->container->getTags($listenerName);

			foreach ($tags as $tag) {
				$this->container->get('event_dispatcher')->addListener(
					$tag['event'],
					$this->container->get($listenerName),
					$tag['method']
				);
			}
		}
	}

	/**
	 * @return void
	 */
	protected function loadRouter()
	{
		$router = $this->getContainer()->get('router');

		$this->annotationsReader->registerAnnotation(new Yampee_Routing_Bridge_Annotation_Route($router));

		/*
		 * Find all the controllers files (in src/controllers)
		 */
		$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
			__APP__.'/src/controllers'
		));

		$classes = array();

		while($it->valid()) {
			if (! $it->isDot()) {
				$classes[] = trim(str_replace(
					array('\\', '/'), '_',
					$it->getSubPath().'_'.$it->getBasename('.php')
				), '_');
			}

			$it->next();
		}

		/*
		 * Find controllers, actions and routes
		 */
		foreach ($classes as $class) {
			if (class_exists($class)) {

				// Prefix
				$reflector = new ReflectionClass($class);
				$classAnnotations = $this->annotationsReader->readReflector($reflector);

				$prefix = '';

				foreach ($classAnnotations as $classAnnotation) {
					if ($classAnnotation instanceof Yampee_Routing_Bridge_Annotation_Route) {
						$prefix = $classAnnotation->pattern;
					}
				}

				// Routes
				$methods = $reflector->getMethods();

				foreach ($methods as $method) {
					if (substr($method->getName(), -6) == 'Action') {
						$annotations = $this->annotationsReader->readReflector($method);

						foreach ($annotations as $annotation) {
							if ($annotation instanceof Yampee_Routing_Bridge_Annotation_Route) {
								$pattern = $prefix.$annotation->pattern;

								if (rtrim($prefix.$annotation->pattern, '/') != '') {
									$pattern = rtrim($pattern, '/');
								}

								$this->container->get('router')->addRoute(new Yampee_Routing_Route(
									$annotation->name,
									$pattern,
									$reflector->getName().'::'.$method->getName(),
									$annotation->defaults,
									$annotation->requirements
								));
							}
						}
					}
				}
			}
		}
	}

	/**
	 * @return array
	 */
	protected function getCoreDefinitions()
	{
		/*
		 * Yampee core container definitions
		 */
		return array(
			// Database
			'database.dsn' => array(
				'class' => 'Yampee_Db_Dsn',
				'arguments' => array(
					'%database.driver%', '%database.database%', '%database.username%',
					'%database.password%', '%database.host%', '%database.port%'
				),
			),
			'database' => array(
				'class' => 'Yampee_Db_Manager',
				'arguments' => array('@database.dsn'),
			),

			// Event dispatcher
			'event_dispatcher' => array(
				'class' => 'Yampee_Ed_Dispatcher',
			),

			// Logger
			'logger.default_storage' => array(
				'class' => 'Yampee_Log_Storage_Filesystem',
				'arguments' => array('%framework.log_file%'),
			),
			'logger' => array(
				'class' => 'Yampee_Log_Logger',
				'arguments' => array('@logger.default_storage'),
			),

			// Router
			'router' => array(
				'class' => 'Yampee_Routing_Router',
			),

			// Translator
			'translator' => array(
				'class' => 'Yampee_Translator_Array',
			),

			// Twig
			'twig.loader' => array(
				'class' => 'Twig_Loader_Filesystem',
				'arguments' => array('%twig.views_dir%'),
			),
			'twig' => array(
				'class' => 'Twig_Environment',
				'arguments' => array('@twig.loader', array(
					'debug' => '%twig.debug%',
					'charset' => '%twig.debug%',
					'cache' => '%twig.cache_dir%',
					'strict_variables' => '%twig.strict_variables%',
				)),
			),
			'twig.extensions.core' => array(
				'class' => 'Yampee_Twig_Core',
				'tags' => array(
					array('name' => 'twig.extension')
				)
			),
			'twig.extensions.translation' => array(
				'class' => 'Yampee_Twig_Translation',
				'arguments' => array('@translator'),
				'tags' => array(
					array('name' => 'twig.extension')
				)
			),

			// YAML
			'yaml' => array(
				'class' => 'Yampee_Yaml_Yaml',
			),
		);
	}

	/**
	 * Generate the root URL from the request informations
	 *
	 * @param Yampee_Http_Request $request
	 * @return Yampee_Locator
	 */
	private function generateRootUrl(Yampee_Http_Request $request)
	{
		$rootDir = str_replace('\\', '/', __APP__);
		$scriptName = $request->get('script_name');
		$requestUri = $request->get('request_uri');

		$rootDirParts = explode('/', $rootDir);
		$scriptNameParts = explode('/', $scriptName);
		$scriptNameFirstPart = '';

		foreach($scriptNameParts as $scriptNameFirstPart) {
			if(! empty($scriptNameFirstPart)) {
				break;
			}
		}

		$documentRoot = array();

		foreach($rootDirParts as $rootDirPart) {
			if($rootDirPart != $scriptNameFirstPart) {
				$documentRoot[] = $rootDirPart;
			} else {
				break;
			}
		}

		$documentRoot = implode('/', $documentRoot);
		$rootUrl = '/'.trim(str_replace($documentRoot, '', $rootDir), '/');
		$requestUri = str_replace($rootUrl, '', $requestUri);

		$this->locator = new Yampee_Locator($documentRoot, $rootUrl, $requestUri);

		return $this->locator;
	}

	/**
	 * @return Yampee_Di_Container
	 */
	public function getContainer()
	{
		return $this->container;
	}

	/**
	 * @return boolean
	 */
	public function isInDev()
	{
		return $this->inDev;
	}

	/**
	 * @return Yampee_Locator
	 */
	public function getLocator()
	{
		return $this->locator;
	}
}