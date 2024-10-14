<?php declare(strict_types = 1);

namespace Spiralle\Clockwork\Stopwatch;

use Spiralle\Clockwork\Scope\ClockworkScope;

final class ClockworkStopwatch
{

	public function __construct(
		private ClockworkScope $clockworkScope,
	)
	{
	}

	/**
	 * @template TReturn
	 * @param callable(): TReturn $callback
	 * @return TReturn
	 */
	public function run(callable $callback, string $description, ?string $color = null, mixed $data = null, bool $timeline = false): mixed
	{
		$clockwork = $this->clockworkScope->getClockworkOrNull();

		if (!$clockwork) {
			return $callback();
		}

		if ($timeline) {
			$event = $clockwork->timeline()->create($description);
		} else {
			$event = $clockwork->event($description);
		}

		$event->color = $color;
		$event->data = $data;

		$event->begin();
		$return = $callback();
		$event->end();

		return $return;
	}

}
