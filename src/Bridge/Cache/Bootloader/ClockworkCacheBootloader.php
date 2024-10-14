<?php declare(strict_types = 1);

namespace Spiralle\Clockwork\Bridge\Cache\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Cache\Bootloader\CacheBootloader;
use Spiral\Cache\Event\CacheHit;
use Spiral\Cache\Event\CacheMissed;
use Spiral\Cache\Event\KeyDeleted;
use Spiral\Cache\Event\KeyWritten;
use Spiral\Events\Bootloader\EventsBootloader;
use Spiral\Events\ListenerFactoryInterface;
use Spiral\Events\ListenerRegistryInterface;
use Spiralle\Clockwork\Bridge\Cache\Listener\ClockworkCacheListener;
use Spiralle\Clockwork\Provider\ClockworkDataSourceProviderRegistry;

final class ClockworkCacheBootloader extends Bootloader
{

	protected const DEPENDENCIES = [
		CacheBootloader::class,
		EventsBootloader::class,
	];

	public function boot(
		ListenerRegistryInterface $listeners,
		ListenerFactoryInterface $listenerFactory,
		ClockworkCacheListener $clockworkCacheListener,
		ClockworkDataSourceProviderRegistry $registry,
	): void
	{
		$listeners->addListener(CacheHit::class, $listenerFactory->create($clockworkCacheListener, 'cacheHit'));
		$listeners->addListener(CacheMissed::class, $listenerFactory->create($clockworkCacheListener, 'cacheMissed'));
		$listeners->addListener(KeyWritten::class, $listenerFactory->create($clockworkCacheListener, 'keyWritten'));
		$listeners->addListener(KeyDeleted::class, $listenerFactory->create($clockworkCacheListener, 'keyDeleted'));

		$registry->addProvider($clockworkCacheListener);
	}

}
