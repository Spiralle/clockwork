<?php declare(strict_types = 1);

namespace Spiralle\Clockwork\Interceptor;

use Spiral\Core\CoreInterceptorInterface;
use Spiral\Core\CoreInterface;
use Spiralle\Clockwork\Scope\ClockworkScope;
use Spiralle\Clockwork\Stopwatch\ClockworkStopwatch;

final class ClockworkInterceptor implements CoreInterceptorInterface
{

	public function __construct(
		private readonly ClockworkStopwatch $clockworkStopwatch,
		private readonly ClockworkScope $clockworkScope,
	)
	{
	}

	/**
	 * @param mixed[] $parameters
	 */
	public function process(string $controller, string $action, array $parameters, CoreInterface $core): mixed
	{
		$clockwork = $this->clockworkScope->getClockworkOrNull();

		if ($clockwork) {
			$request = $clockwork->request();
			$request->controller = sprintf('%s@%s', $controller, $action);
		}

		return $this->clockworkStopwatch->run(
			callback: fn (): mixed => $core->callAction($controller, $action, $parameters),
			description: 'Controller',
			color: 'purple',
			data: $parameters,
			timeline: true,
		);
	}

}
