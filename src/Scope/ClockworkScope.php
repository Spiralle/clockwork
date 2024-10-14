<?php declare(strict_types = 1);

namespace Spiralle\Clockwork\Scope;

use Clockwork\Clockwork;
use LogicException;

final class ClockworkScope
{

	public function __construct(
		private readonly ?Clockwork $clockwork,
	)
	{
	}

	public function getClockwork(): Clockwork
	{
		return $this->clockwork ?? throw new LogicException('Clockwork instance is not available.');
	}

	public function getClockworkOrNull(): ?Clockwork
	{
		return $this->clockwork;
	}

}
