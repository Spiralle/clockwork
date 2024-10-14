<?php declare(strict_types = 1);

namespace Spiralle\Clockwork\Log;

use Monolog\Handler\Handler;
use Monolog\LogRecord;
use Spiralle\Clockwork\Scope\ClockworkScope;

final class ClockworkLogger extends Handler
{

	public function __construct(
		private readonly ClockworkScope $scope,
	)
	{
	}

	public function isHandling(LogRecord $record): bool
	{
		return (bool) $this->scope->getClockworkOrNull();
	}

	public function handle(LogRecord $record): bool
	{
		$clockwork = $this->scope->getClockworkOrNull();

		if (!$clockwork) {
			return false;
		}

		$clockwork->log($record->level->toPsrLogLevel(), $record->message, $record->context);

		return true;
	}

}
