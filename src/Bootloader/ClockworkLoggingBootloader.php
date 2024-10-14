<?php declare(strict_types = 1);

namespace Spiralle\Clockwork\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Core\Attribute\Singleton;
use Spiral\Monolog\Bootloader\MonologBootloader;
use Spiralle\Clockwork\Log\ClockworkLogger;

#[Singleton]
final class ClockworkLoggingBootloader extends Bootloader
{

	public function boot(MonologBootloader $monologBootloader, ClockworkLogger $logger): void
	{
		$monologBootloader->addHandler('clockwork', $logger);
		$monologBootloader->addHandler('stdout', $logger);
		$monologBootloader->addHandler('stderr', $logger);
	}

}
