<?php declare(strict_types = 1);

namespace Spiralle\Clockwork\Bridge\Cycle\Bootloader;

use Cycle\Database\Driver\MySQL\MySQLDriver;
use Cycle\Database\Driver\Postgres\PostgresDriver;
use Cycle\Database\Driver\SQLite\SQLiteDriver;
use Cycle\Database\Driver\SQLServer\SQLServerDriver;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Core\Attribute\Singleton;
use Spiral\Cycle\Bootloader\CycleOrmBootloader;
use Spiral\Monolog\Bootloader\MonologBootloader;
use Spiralle\Clockwork\Bridge\Cycle\Logger\ClockworkDatabaseLogger;
use Spiralle\Clockwork\Provider\ClockworkDataSourceProviderRegistry;

#[Singleton]
final class ClockworkCycleBootloader extends Bootloader
{

	protected const DEPENDENCIES = [
		CycleOrmBootloader::class,
		MonologBootloader::class,
	];

	public function boot(
		ClockworkDataSourceProviderRegistry $providers,
		MonologBootloader $monologBootloader,
		ClockworkDatabaseLogger $logger,
	): void
	{
		$monologBootloader->addHandler(MySQLDriver::class, $logger);
		$monologBootloader->addHandler(SQLiteDriver::class, $logger);
		$monologBootloader->addHandler(PostgresDriver::class, $logger);
		$monologBootloader->addHandler(SQLServerDriver::class, $logger);

		$providers->addProvider($logger);
	}

}
