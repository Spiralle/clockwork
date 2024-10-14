<?php declare(strict_types = 1);

namespace Spiralle\Clockwork\Log;

use Clockwork\Clockwork;
use Clockwork\Request\LogLevel;

final class ClockworkGlobalLogger
{

	private static ?Clockwork $clockwork = null;

	/**
	 * @param mixed[] $context
	 */
	public static function log(mixed $message, string $level = LogLevel::DEBUG, array $context = []): void
	{
		$clockwork = self::$clockwork;

		if (!$clockwork) {
			return;
		}

		$clockwork->log($level, $message, $context);
	}

	/**
	 * @internal
	 */
	public static function instance(Clockwork $clockwork): void
	{
		self::$clockwork = $clockwork;
	}

}
