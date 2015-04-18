<?php

namespace Bleicker\Request\Http;

use Bleicker\Controller\ControllerInterface;
use Bleicker\Framework\WebApplication;
use Bleicker\Request\ApplicationRequest;
use Bleicker\Request\HandlerInterface;
use Bleicker\Request\Http\Exception\ControllerRouteDataInterfaceRequiredException;
use Bleicker\Request\Http\Exception\MethodNotSupportedException;
use Bleicker\Request\Http\Exception\NotFoundException;
use Bleicker\Request\MainRequestInterface;
use Bleicker\Response\ApplicationResponse;
use Bleicker\Response\Http\Response;
use Bleicker\Response\MainResponseInterface;
use Bleicker\Routing\ControllerRouteDataInterface;
use Bleicker\Routing\RouteInterface;
use Bleicker\Routing\RouterInterface;
use Bleicker\View\ViewInterface;
use Bleicker\View\ViewResolverInterface;

/**
 * Class Handler
 *
 * @package Bleicker\Request\Http
 */
class Handler implements HandlerInterface {

	/**
	 * @var MainRequestInterface
	 */
	protected $request;

	/**
	 * @var MainResponseInterface
	 */
	protected $response;

	/**
	 * @var ViewResolverInterface
	 */
	protected $viewResolver;

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
	 * @var ViewInterface
	 */
	protected $view;

	/**
	 * @return $this
	 */
	public function initialize() {
		$this->request = new ApplicationRequest(WebApplication::getRegistry()->getImplementation(MainRequestInterface::class));
		$this->response = new ApplicationResponse(WebApplication::getRegistry()->getImplementation(MainResponseInterface::class));
		$this->viewResolver = WebApplication::getRegistry()->getImplementation(ViewResolverInterface::class);

		$this->router = WebApplication::getRegistry()->getImplementation(RouterInterface::class);

		$routerInformation = $this->invokeRouter();
		$this->controllerName = $this->getControllerNameByRoute($routerInformation[1]);
		$this->methodName = $this->getMethodNameByRoute($routerInformation[1]);
		$this->methodArguments = $this->getMethodArgumentsByRouterInformation($routerInformation);
		$this->view = $this->getView();
		
		return $this;
	}

	/**
	 * @return ViewInterface
	 */
	protected function getView() {
		return $this->viewResolver->setControllerName($this->controllerName)->setMethodName($this->methodName)->resolve();
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
	 * @param array $routerInformation
	 * @return array
	 */
	protected function getMethodArgumentsByRouterInformation(array $routerInformation = array()) {
		return array_key_exists(2, $routerInformation) ? (array)$routerInformation[2] : [];
	}

	/**
	 * @todo reflection of callable controller->method and passing arguments in right order
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
			->setView($this->view);

		$content = call_user_func_array(array($controller, $this->methodName), $this->methodArguments);

		if ($content === NULL && $controller->hasView()) {
			$content = $controller->getView()->render();
		}

		/** @var Response $httpResponse */
		$httpResponse = $this->response->getMainResponse();
		$httpResponse->setContent($content);

		return $this;
	}

	/**
	 * @return array
	 * @throws Exception\NotFoundException
	 * @throws Exception\MethodNotSupportedException
	 */
	protected function invokeRouter() {
		/** @var Request $request */
		$request = $this->request->getMainRequest();
		$routeResult = $this->router->dispatch($request->getPathInfo(), $request->getMethod());
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
