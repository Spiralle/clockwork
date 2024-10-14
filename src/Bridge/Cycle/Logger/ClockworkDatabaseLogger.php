<?php declare(strict_types = 1);

namespace Spiralle\Clockwork\Bridge\Cycle\Logger;

use Clockwork\DataSource\DataSourceInterface;
use Clockwork\Request\Request;
use Monolog\Handler\AbstractHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Spiralle\Clockwork\DataSource\CallableDataSource;
use Spiralle\Clockwork\Provider\ClockworkDataSourceProvider;

final class ClockworkDatabaseLogger extends AbstractHandler implements ClockworkDataSourceProvider
{

	public static int $slowQueryThreshold = 1000; // ms

	/** @var array{ query: string, duration: float|null, data: mixed[] }[] */
	private array $queries = [];

	private int $queriesCount = 0;

	private int $slowQueries = 0;

	private int $selects = 0;

	private int $inserts = 0;

	private int $updates = 0;

	private int $deletes = 0;

	private int $others = 0;

	public function handle(LogRecord $record): bool
	{
		if ($record->level !== Level::Info) {
			return false;
		}

		$elapsed = $this->getElapsed($record);

		$this->incrementQueryCount($record->message, $elapsed);

		$this->queries[] = [
			'query' => $record->message,
			'duration' => $elapsed * 1000,
			'data' => [
				'time' => microtime(true) - ($elapsed ?: 0),
				'connection' => $record->context['driver'] ?? null,
				'tags' => $this->isSlowQuery($elapsed) ? ['slow'] : [],
			],
		];

		return true;
	}

	public function reset(): void
	{
		$this->queries = [];
		$this->queriesCount = 0;
		$this->slowQueries = 0;
		$this->selects = 0;
		$this->inserts = 0;
		$this->updates = 0;
		$this->deletes = 0;
		$this->others = 0;
	}

	public function provide(): DataSourceInterface
	{
		return new CallableDataSource(function (Request $request): void {
			foreach ($this->queries as $query) {
				$request->addDatabaseQuery($query['query'], [], $query['duration'], $query['data']);
			}

			$request->databaseQueriesCount = $this->incrementCounter($request->databaseQueriesCount, $this->queriesCount);
			$request->databaseSlowQueries = $this->incrementCounter($request->databaseSlowQueries, $this->slowQueries);
			$request->databaseSelects = $this->incrementCounter($request->databaseSelects, $this->selects);
			$request->databaseInserts = $this->incrementCounter($request->databaseInserts, $this->inserts);
			$request->databaseUpdates = $this->incrementCounter($request->databaseUpdates, $this->updates);
			$request->databaseDeletes = $this->incrementCounter($request->databaseDeletes, $this->deletes);
			$request->databaseOthers = $this->incrementCounter($request->databaseOthers, $this->others);
		});
	}

	private function incrementCounter(mixed $databaseQueriesCount, int $count): int
	{
		if (!is_int($databaseQueriesCount)) {
			return $count;
		}

		return $databaseQueriesCount + $count;
	}

	private function incrementQueryCount(string $query, ?float $elapsed): void
	{
		$sql = ltrim($query);

		$this->queriesCount++;

		if (preg_match('/^select\b/i', $sql)) {
			$this->selects++;
		} elseif (preg_match('/^insert\b/i', $sql)) {
			$this->inserts++;
		} elseif (preg_match('/^update\b/i', $sql)) {
			$this->updates++;
		} elseif (preg_match('/^delete\b/i', $sql)) {
			$this->deletes++;
		} else {
			$this->others++;
		}

		if ($this->isSlowQuery($elapsed)) {
			$this->slowQueries++;
		}
	}

	private function getElapsed(LogRecord $record): ?float
	{
		if (!isset($record->context['elapsed'])) {
			return null;
		}

		if (is_numeric($record->context['elapsed'])) {
			return (float) $record->context['elapsed'];
		}

		return null;
	}

	private function isSlowQuery(?float $elapsed): bool
	{
		return $elapsed !== null && $elapsed > self::$slowQueryThreshold;
	}

}
