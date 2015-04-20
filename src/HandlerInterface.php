<?php

namespace Bleicker\Request;

/**
 * Interface HandlerInterface
 *
 * @package Bleicker\Request
 */
interface HandlerInterface {

	/**
	 * @return $this
	 */
	public function initialize();

	/**
	 * @return $this
	 */
	public function handle();
}
