<?php

namespace Bleicker\Request;

/**
 * Class AbstractRequest
 *
 * @package Bleicker\Request
 */
abstract class AbstractRequest implements RequestInterface {

	/**
	 * @var RequestInterface
	 */
	protected $parentRequest;

	/**
	 * @param RequestInterface $parentRequest
	 */
	public function __construct(RequestInterface $parentRequest = NULL) {
		$this->parentRequest = $parentRequest;
	}

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
