<?php declare(strict_types = 1);

namespace Spiralle\Clockwork\DataSource;

use Clockwork\DataSource\DataSource;
use Clockwork\Request\Request;

final class CallableDataSource extends DataSource
{

	/** @var callable(Request): void */
	private $resolve;

	/**
	 * @param callable(Request): void $resolve
	 */
	public function __construct(
		callable $resolve,
	)
	{
		$this->resolve = $resolve;
	}

	public function resolve(Request $request): Request
	{
		($this->resolve)($request);

		return $request;
	}

}
