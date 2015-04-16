<?php

namespace Bleicker\Request;

/**
 * Interface RequestInterface
 *
 * @package Bleicker\Request
 */
interface RequestInterface {

	/**
	 * @return RequestInterface
	 */
	public function getParentRequest();

	/**
	 * @param RequestInterface $parentRequest
	 * @return $this
	 */
	public function setParentRequest(RequestInterface $parentRequest);

	/**
	 * @return RequestInterface
	 */
	public function getMainRequest();

	/**
	 * @return boolean
	 */
	public function isMainRequest();
}
