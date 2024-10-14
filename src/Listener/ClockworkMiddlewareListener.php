<?php declare(strict_types = 1);

namespace Spiralle\Clockwork\Listener;

use Clockwork\DataSource\DataSourceInterface;
use Clockwork\Request\Request;
use Spiral\Http\Event\MiddlewareProcessing;
use Spiralle\Clockwork\DataSource\CallableDataSource;
use Spiralle\Clockwork\Provider\ClockworkDataSourceProvider;

final class ClockworkMiddlewareListener implements ClockworkDataSourceProvider
{

	/** @var string[] */
	private array $middlewares = [];

	public function __invoke(MiddlewareProcessing $processing): void
	{
		$this->middlewares[] = $processing->middleware::class;
	}

	public function provide(): DataSourceInterface
	{
		return new CallableDataSource(function (Request $request): void {
			$request->middleware = $this->middlewares;
		});
	}

}
