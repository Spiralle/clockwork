<?php declare(strict_types = 1);

namespace Spiralle\Clockwork\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Core\Attribute\Singleton;
use Spiral\Events\Bootloader\EventsBootloader;
use Spiral\Events\ListenerFactoryInterface;
use Spiral\Events\ListenerRegistryInterface;
use Spiral\Http\Event\MiddlewareProcessing;
use Spiralle\Clockwork\Listener\ClockworkMiddlewareListener;
use Spiralle\Clockwork\Provider\ClockworkDataSourceProviderRegistry;

#[Singleton]
final class ClockworkMiddlewareBootloader extends Bootloader
{

	protected const DEPENDENCIES = [
		EventsBootloader::class,
	];

	public function boot(
		ListenerRegistryInterface $listeners,
		ListenerFactoryInterface $listenerFactory,
		ClockworkDataSourceProviderRegistry $providers,
		ClockworkMiddlewareListener $middlewareListener,
	): void
	{
		$listeners->addListener(
			MiddlewareProcessing::class,
			$listenerFactory->create($middlewareListener, '__invoke'),
		);

		$providers->addProvider($middlewareListener);
	}

}
