<?php declare(strict_types = 1);

namespace Spiralle\Clockwork\Scope;

use Clockwork\Clockwork;
use Spiral\Core\Container;

final class ClockworkScope
{

	public function __construct(
		private readonly Container $container,
	)
	{
	}

	public function getClockwork(): Clockwork
	{
		return $this->container->get(Clockwork::class);
	}

	public function getClockworkOrNull(): ?Clockwork
	{
		if ($this->container->has(Clockwork::class)) {
			return $this->container->get(Clockwork::class);
		}

		return null;
	}

}
