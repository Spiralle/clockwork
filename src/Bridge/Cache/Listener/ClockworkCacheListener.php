<?php declare(strict_types = 1);

namespace Spiralle\Clockwork\Bridge\Cache\Listener;

use Clockwork\DataSource\DataSourceInterface;
use Clockwork\Request\Request;
use Spiral\Cache\Event\CacheHit;
use Spiral\Cache\Event\CacheMissed;
use Spiral\Cache\Event\KeyDeleted;
use Spiral\Cache\Event\KeyWritten;
use Spiralle\Clockwork\DataSource\CallableDataSource;
use Spiralle\Clockwork\Provider\ClockworkDataSourceProvider;

final class ClockworkCacheListener implements ClockworkDataSourceProvider
{

	private const MissValue = '';

	/** @var array{type: string, key: string, value: mixed}[] */
	private array $events = [];

	private int $hits = 0;

	private int $reads = 0;

	private int $writes = 0;

	private int $deletes = 0;

	public function cacheHit(CacheHit $hit): void
	{
		$this->events[] = ['type' => 'hit', 'key' => $hit->key, 'value' => $hit->value];

		$this->hits++;
		$this->reads++;
	}

	public function cacheMissed(CacheMissed $hit): void
	{
		$this->events[] = ['type' => 'miss', 'key' => $hit->key, 'value' => self::MissValue];

		$this->reads++;
	}

	public function keyDeleted(KeyDeleted $keyDeleted): void
	{
		$this->events[] = ['type' => 'delete', 'key' => $keyDeleted->key, 'value' => self::MissValue];

		$this->deletes++;
	}

	public function keyWritten(KeyWritten $keyWritten): void
	{
		$this->events[] = ['type' => 'write', 'key' => $keyWritten->key, 'value' => $keyWritten->value];

		$this->writes++;
	}

	public function provide(): DataSourceInterface
	{
		return new CallableDataSource(function (Request $request): void {
			foreach ($this->events as $event) {
				$request->addCacheQuery($event['type'], $event['key'], $event['value']);
			}

			$request->cacheHits = $this->hits;
			$request->cacheDeletes = $this->deletes;
			$request->cacheReads = $this->reads;
			$request->cacheWrites = $this->writes;
		});
	}

}
