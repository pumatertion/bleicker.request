<?php

namespace Bleicker\Request;

use Bleicker\Response\MainResponseInterface;

/**
 * Interface HandlerInterface
 *
 * @package Bleicker\Request
 */
interface HandlerInterface {

	/**
	 * @param MainRequestInterface $request
	 * @param MainResponseInterface $response
	 */
	public function __construct(MainRequestInterface $request, MainResponseInterface $response);

	/**
	 * @return $this
	 */
	public function handle();
}
