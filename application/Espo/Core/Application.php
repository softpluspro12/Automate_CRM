<?php

namespace Espo\Core;


class Application
{
	private $metadata;

	private $container;

	private $slim;
	
	private $auth;

	/**
     * Constructor
     */
    public function __construct()
    {
    	$this->container = new Container();

		$GLOBALS['log'] = $this->container->get('log');		
		/*$GLOBALS['log'] = $this->container->get('logOld');	

        set_error_handler(array($GLOBALS['log'], 'catchError'), E_ALL);
		set_exception_handler(array($GLOBALS['log'], 'catchException'));*/	
			
		date_default_timezone_set('UTC');			
    }

	public function getSlim()
	{
		if (empty($this->slim)) {
			$this->slim = $this->container->get('slim');
		}
		return $this->slim;
	}

	public function getMetadata()
	{
		if (empty($this->metadata)) {
			$this->metadata = $this->container->get('metadata');
		}
		return $this->metadata;
	}
	
    protected function getAuth()
    {
    	if (empty($this->auth)) {
    		$this->auth = new \Espo\Core\Utils\Auth($this->container);
    	}
    	return $this->auth;
    }

	public function getContainer()
	{
		return $this->container;
	}

    public function run($name = 'default')
    {
        $this->routeHooks();
        $this->initRoutes();
        $this->getSlim()->run();
    }
    
    public function runEntryPoint($entryPoint)
    {	
    	if (empty($entryPoint)) {
    		throw new \Error();
    	}
    	
    	$slim = $this->getSlim();
    	$container = $this->getContainer();
    	
		$slim->get('/', function() {});
    	
    	$entryPointManager = new \Espo\Core\EntryPointManager($container);    	
    	
    	$auth = $this->getAuth();
    	$apiAuth = new \Espo\Core\Utils\Api\Auth($auth, $entryPointManager->checkAuthRequired($entryPoint), true);
    	$slim->add($apiAuth);    	
    	
		$slim->hook('slim.before.dispatch', function () use ($entryPoint, $entryPointManager, $container) {			
			try {
				$entryPointManager->run($entryPoint); 
			} catch (\Exception $e) {
				$container->get('output')->processError($e->getMessage(), $e->getCode());
			}
		});
    	
    	$slim->run();
    }
    
    public function runCron()
    {
    	$auth = $this->getAuth();
    	$auth->useNoAuth();  	
    	
    	$cronManager = new \Espo\Core\CronManager($this->container);
		$cronManager->run();
    }
    
	protected function routeHooks()
	{
		$container = $this->getContainer();
		$slim = $this->getSlim();
		
		$auth = $this->getAuth();
		
		$apiAuth = new \Espo\Core\Utils\Api\Auth($auth);
		$this->getSlim()->add($apiAuth);

		$this->getSlim()->hook('slim.before.dispatch', function () use ($slim, $container) {

			$route = $slim->router()->getCurrentRoute();
		    $conditions = $route->getConditions();

			if (isset($conditions['useController']) && $conditions['useController'] == false) {
				return;
			}

			$routeOptions = call_user_func($route->getCallable());
			$routeKeys = is_array($routeOptions) ? array_keys($routeOptions) : array();

			if (!in_array('controller', $routeKeys, true)) {
				return $container->get('output')->render($routeOptions);
			}

			$params = $route->getParams();
			$data = $slim->request()->getBody();

			foreach ($routeOptions as $key => $value) {
				if (strstr($value, ':')) {
					$paramName = str_replace(':', '', $value);
					$value = $params[$paramName];
				}
				$controllerParams[$key] = $value;
			}
			
			$params = array_merge($params, $controllerParams);

			$controllerName = ucfirst($controllerParams['controller']);
			
			if (!empty($controllerParams['action'])) {
				$actionName = $controllerParams['action'];
			} else {
				$httpMethod = strtolower($slim->request()->getMethod());
				$crudList = $container->get('config')->get('crud');
				$actionName = $crudList[$httpMethod];
			}
			
			try {							
				$controllerManager = new \Espo\Core\ControllerManager($container);
				$result = $controllerManager->process($controllerName, $actionName, $params, $data, $slim->request());
				$container->get('output')->render($result);
			} catch (\Exception $e) {
				$container->get('output')->processError($e->getMessage(), $e->getCode());
			}
		});

		$this->getSlim()->hook('slim.after.router', function () use (&$slim) {
			$slim->contentType('application/json');
		});
	}


	protected function initRoutes()
	{
		$routes = new \Espo\Core\Utils\Route($this->getContainer()->get('config'), $this->getContainer()->get('fileManager'));
		$crudList = array_keys( $this->getContainer()->get('config')->get('crud') );

		foreach ($routes->getAll() as $route) {

			$method = strtolower($route['method']);
			if (!in_array($method, $crudList)) {
				$GLOBALS['log']->error('Route: Method ['.$method.'] does not exist. Please check your route ['.$route['route'].']');
				continue;
			}

            $currentRoute = $this->getSlim()->$method($route['route'], function() use ($route) {   //todo change "use" for php 5.4
	        	return $route['params'];
			});

			if (isset($route['conditions'])) {
            	$currentRoute->conditions($route['conditions']);
			}
		}
	}
}

