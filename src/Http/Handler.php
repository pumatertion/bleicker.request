<?php

namespace Bleicker\Request\Http;

use Bleicker\Controller\ControllerInterface;
use Bleicker\Framework\Utility\Arrays;
use Bleicker\Framework\Utility\ObjectManager;
use Bleicker\Request\ApplicationRequest;
use Bleicker\Request\ApplicationRequestInterface;
use Bleicker\Request\HandlerInterface;
use Bleicker\Request\Http\Exception\ControllerRouteDataInterfaceRequiredException;
use Bleicker\Request\Http\Exception\MethodNotSupportedException;
use Bleicker\Request\Http\Exception\NotFoundException;
use Bleicker\Request\MainRequestInterface;
use Bleicker\Response\ApplicationResponse;
use Bleicker\Response\Http\Response;
use Bleicker\Response\MainResponseInterface;
use Bleicker\Response\ResponseInterface as ApplicationResponseInterface;
use Bleicker\Routing\ControllerRouteDataInterface;
use Bleicker\Routing\RouteInterface;
use Bleicker\Routing\RouterInterface;
use ReflectionMethod;
use ReflectionParameter;

/**
 * Class Handler
 *
 * @package Bleicker\Request\Http
 */
class Handler implements HandlerInterface {

	/**
	 * @var ApplicationRequestInterface
	 */
	protected $request;

	/**
	 * @var ApplicationResponseInterface
	 */
	protected $response;

	/**
	 * @var RouterInterface
	 */
	protected $router;

	/**
	 * @var string
	 */
	protected $controllerName;

	/**
	 * @var string
	 */
	protected $methodName;

	/**
	 * @var array
	 */
	protected $methodArguments;

	/**
	 * @return $this
	 */
	public function initialize() {
		$this->request = new ApplicationRequest(ObjectManager::get(MainRequestInterface::class));
		$this->response = new ApplicationResponse(ObjectManager::get(MainResponseInterface::class));
		$this->router = ObjectManager::get(RouterInterface::class);

		$routerInformation = $this->invokeRouter();
		$this->controllerName = $this->getControllerNameByRoute($routerInformation[1]);
		$this->methodName = $this->getMethodNameByRoute($routerInformation[1]);
		$this->methodArguments = $this->getMethodArgumentsByRouterInformation($this->controllerName, $this->methodName, $routerInformation[2]);

		return $this;
	}

	/**
	 * @param RouteInterface $route
	 * @return string
	 * @throws ControllerRouteDataInterfaceRequiredException
	 */
	protected function getControllerNameByRoute(RouteInterface $route) {
		/** @var ControllerRouteDataInterface $controllerRouteData */
		$controllerRouteData = $route->getData();

		if (!($controllerRouteData instanceof ControllerRouteDataInterface)) {
			throw new ControllerRouteDataInterfaceRequiredException('No instance of ControllerRouteDataInterface given', 1429338660);
		}

		return $controllerRouteData->getController();
	}

	/**
	 * @param RouteInterface $route
	 * @return string
	 * @throws ControllerRouteDataInterfaceRequiredException
	 */
	protected function getMethodNameByRoute(RouteInterface $route) {
		/** @var ControllerRouteDataInterface $controllerRouteData */
		$controllerRouteData = $route->getData();

		if (!($controllerRouteData instanceof ControllerRouteDataInterface)) {
			throw new ControllerRouteDataInterfaceRequiredException('No instance of ControllerRouteDataInterface given', 1429338661);
		}

		return $controllerRouteData->getMethod();
	}

	/**
	 * @param string $controllerName
	 * @param string $methodName
	 * @param array $arguments
	 * @return array
	 */
	protected function getMethodArgumentsByRouterInformation($controllerName, $methodName, array $arguments = array()) {
		$methodArguments = [];
		$methodReflection = new \ReflectionMethod($controllerName, $methodName);
		$availableParameters = $methodReflection->getParameters();
		/** @var ReflectionParameter $parameter */
		foreach($availableParameters as $parameter){
			$methodArguments[$parameter->getName()] = Arrays::getValueByPath($arguments, $parameter->getName());
		}
		return $methodArguments;
	}

	/**
	 * @todo mapping to objects here?
	 * @return $this
	 * @throws ControllerRouteDataInterfaceRequiredException
	 */
	public function handle() {

		/** @var ControllerInterface $controller */
		$controller = new $this->controllerName();
		$controller
			->setRequest($this->request)
			->setResponse($this->response)
			->resolveFormat($this->methodName)
			->resolveView($this->methodName);

		$content = call_user_func_array(array($controller, $this->methodName), $this->methodArguments);

		if(!empty($content)){
			/** @var Response $httpResponse */
			$httpResponse = $this->response->getMainResponse();
			$httpResponse->setContent($content);
		}

		return $this;
	}

	/**
	 * @return array
	 * @throws Exception\NotFoundException
	 * @throws Exception\MethodNotSupportedException
	 */
	protected function invokeRouter() {
		$routeResult = $this->router->dispatch($this->request->getMainRequest()->getPathInfo(), $this->request->getMainRequest()->getMethod());
		switch ($routeResult[0]) {
			case RouterInterface::NOT_FOUND:
				throw new NotFoundException('Not Found', 1429187150);
			case RouterInterface::METHOD_NOT_ALLOWED:
				throw new MethodNotSupportedException('Method not allowed', 1429187151);
			case RouterInterface::FOUND:
				return $routeResult;
		}
	}
}
