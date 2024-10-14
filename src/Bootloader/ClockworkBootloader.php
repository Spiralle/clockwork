<?php declare(strict_types = 1);

namespace Spiralle\Clockwork\Bootloader;

use Clockwork\Clockwork;
use Psr\Http\Message\ServerRequestInterface;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Bootloader\Http\Exception\ContextualObjectNotFoundException;
use Spiral\Bootloader\Http\Exception\InvalidRequestScopeException;
use Spiral\Core\Attribute\Singleton;
use Spiral\Core\BinderInterface;
use Spiral\Core\Config\Proxy;
use Spiralle\Clockwork\Middleware\ClockworkMiddleware;
use Spiralle\Clockwork\Scope\ClockworkScope;

#[Singleton]
final class ClockworkBootloader extends Bootloader
{

	protected const SINGLETONS = [
		ClockworkScope::class => [self::class, 'clockworkScope'],
	];

	public function __construct(
		private readonly BinderInterface $binder,
	)
	{
	}

	public function defineBindings(): array
	{
		$this->binder
			->getBinder('http')
			->bind(
				ClockworkScope::class,
				static function (?ServerRequestInterface $request): ClockworkScope {
					if (!$request) {
						throw new InvalidRequestScopeException(ClockworkScope::class);
					}

					$clockwork = $request->getAttribute(ClockworkMiddleware::Attribute);

					if (!$clockwork) {
						throw new ContextualObjectNotFoundException(ClockworkScope::class, ClockworkMiddleware::Attribute);
					}

					return $clockwork;
				},
			);

		$this->binder->bind(ClockworkScope::class, new Proxy(ClockworkScope::class, false));

		return [];
	}

	protected function clockworkScope(ServerRequestInterface $request): ClockworkScope
	{
		$clockwork = $request->getAttribute(ClockworkMiddleware::Attribute);

		if (!$clockwork instanceof Clockwork) {
			$clockwork = null;
		}

		return new ClockworkScope($clockwork);
	}

}
