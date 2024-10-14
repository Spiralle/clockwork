<?php declare(strict_types = 1);

namespace Spiralle\Clockwork\Provider;

use Clockwork\Clockwork;
use Spiral\Core\Attribute\Singleton;

#[Singleton]
final class ClockworkDataSourceProviderRegistry
{

	/** @var ClockworkDataSourceProvider[] */
	private array $providers = [];

	public function addProvider(ClockworkDataSourceProvider $provider): self
	{
		$this->providers[] = $provider;

		return $this;
	}

	public function injectTo(Clockwork $clockwork): void
	{
		foreach ($this->providers as $provider) {
			$clockwork->addDataSource($provider->provide());
		}
	}

}
