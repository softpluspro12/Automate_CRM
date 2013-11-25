<?php

namespace Espo\Core\Utils\Api;

use \Slim\Slim;

class Auth extends \Slim\Middleware
{
	private $entityManager;
	
	private $container;

	protected $realm = '';

	public function __construct(\Doctrine\ORM\EntityManager $entityManager, \Espo\Core\Container $container)
	{
		$this->entityManager = $entityManager;
		$this->container = $container;
	}

	function call()
	{
		$req = $this->app->request();
        $res = $this->app->response();

		$uri = $req->getResourceUri();
		$httpMethod = $req->getMethod();

		/**
		* Check if user credentials are required for current route
		*/
		$routes = $this->app->router()->getMatchedRoutes($httpMethod, $uri);

		if (!empty($routes[0])) {
			$routeConditions = $routes[0]->getConditions();
        	if (isset($routeConditions['auth']) && $routeConditions['auth'] === false) {
	        	$this->next->call();
				return;
			}
		}

		$authKey = $req->headers('PHP_AUTH_USER');
        $authSec = $req->headers('PHP_AUTH_PW');

        if ($authKey && $authSec) {

			$isAuthenticated = false;
			
			$username = $authKey;
			$password = $authSec;

		    $user = $this->entityManager->getRepository('\Espo\Entities\User')->findOneBy(array('username' => $username));
			if ($user) {
				if ($password == $user->getPassword()) {
					$this->container->setUser($user);
					$isAuthenticated = true;
				}
			}		

            if ($isAuthenticated) {
                $this->next->call();
            } else {
            	$res->header('WWW-Authenticate', sprintf('Basic realm="%s"', $this->realm));
            	$res->status(401);
            }
        } else {
            $res->header('WWW-Authenticate', sprintf('Basic realm="%s"', $this->realm));
            $res->status(401);
        }
	}
}
