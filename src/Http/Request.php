<?php

namespace Bleicker\Request\Http;

use Bleicker\Request\RequestInterface;
use Bleicker\Request\MainRequestInterface;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

/**
 * Class Request
 *
 * @package Bleicker\Request\Http
 */
class Request extends HttpRequest implements MainRequestInterface {

	/**
	 * @var RequestInterface
	 */
	protected $parentRequest;

	/**
	 * @return RequestInterface
	 */
	public function getParentRequest() {
		return $this->parentRequest;
	}

	/**
	 * @param RequestInterface $parentRequest
	 * @return $this
	 */
	public function setParentRequest(RequestInterface $parentRequest) {
		$this->parentRequest = $parentRequest;
		return $this;
	}

	/**
	 * @return RequestInterface
	 */
	public function getMainRequest() {
		$parentRequest = $this->getParentRequest();
		if ($parentRequest === NULL) {
			return $this;
		}
		if ($parentRequest->getParentRequest() instanceof RequestInterface) {
			return $parentRequest->getParentRequest();
		}
		return $parentRequest;
	}

	/**
	 * @return boolean
	 */
	public function isMainRequest() {
		return $this->getMainRequest() === $this;
	}
}
