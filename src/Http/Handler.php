<?php

namespace Bleicker\Request\Http;

use Bleicker\Controller\ControllerInterface;
use Bleicker\FastRouter\Router;
use Bleicker\Request\ApplicationRequest;
use Bleicker\Request\HandlerInterface;
use Bleicker\Request\Http\Exception\ControllerRouteDataInterfaceRequiredException;
use Bleicker\Request\Http\Exception\MethodNotSupportedException;
use Bleicker\Request\Http\Exception\NotFoundException;
use Bleicker\Request\MainRequestInterface;
use Bleicker\Response\ApplicationResponse;
use Bleicker\Response\MainResponseInterface;
use Bleicker\Routing\ControllerRouteDataInterface;
use Bleicker\Routing\RouterInterface;
use Bleicker\View\ViewResolver;
use Bleicker\View\ViewResolverInterface;
use Bleicker\Response\Http\Response;
use Bleicker\Routing\RouteInterface;
use Bleicker\Routing\RouteDataInterface;

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
	 * @param MainRequestInterface $request
	 * @param MainResponseInterface $response
	 */
	public function __construct(MainRequestInterface $request, MainResponseInterface $response) {
		$this->viewResolver = new ViewResolver();
		$this->viewResolver->setRequest($request);
		$this->request = new ApplicationRequest($request);
		$this->response = new ApplicationResponse($response);
		$this->router = Router::getInstance();
	}

	/**
	 * @todo reflection of callable controller->method and passing arguments in right order
	 * @todo mapping to objects here?
	 * @return $this
	 * @throws ControllerRouteDataInterfaceRequiredException
	 */
	public function handle() {

		$routerInformations = $this->invokeRouter();

		/** @var RouteInterface $route */
		$route = $routerInformations[1];

		/** @var ControllerRouteDataInterface $controllerRouteData */
		$controllerRouteData = $route->getData();

		if (!($controllerRouteData instanceof ControllerRouteDataInterface)) {
			throw new ControllerRouteDataInterfaceRequiredException('No instance of ControllerRouteDataInterface given', 1429187153);
		}

		$controllerName = $controllerRouteData->getController();
		$methodName = $controllerRouteData->getMethod();
		$methodArguments = array_key_exists(2, $routerInformations) ? (array)$routerInformations[2] : [];

		/** @var ControllerInterface $controller */
		$controller = new $controllerName();
		$controller
			->setRequest($this->request)
			->setResponse($this->response)
			->setViewResolver($this->viewResolver)
			->setView($controller->getViewResolver()->resolve())
			->initialize();
		$content = call_user_func_array(array($controller, $methodName), $methodArguments);
		if ($content === NULL) {
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
