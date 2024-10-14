<?php declare(strict_types = 1);

namespace Spiralle\Clockwork\Middleware;

use Clockwork\Authentication\AuthenticatorInterface;
use Clockwork\Authentication\NullAuthenticator;
use Clockwork\Clockwork;
use Clockwork\DataSource\PsrMessageDataSource;
use Clockwork\Helpers\ServerTiming;
use Clockwork\Request\Request;
use Clockwork\Storage\FileStorage;
use Clockwork\Storage\StorageInterface;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spiral\Boot\DirectoriesInterface;
use Spiral\Boot\EnvironmentInterface;
use Spiral\Core\Container;
use Spiral\Core\ScopeInterface;
use Spiral\Events\ListenerFactoryInterface;
use Spiral\Events\ListenerRegistryInterface;
use Spiral\Http\Event\MiddlewareProcessing;
use Spiralle\Clockwork\Listener\ClockworkMiddlewareListener;
use Spiralle\Clockwork\Log\ClockworkGlobalLogger;
use Spiralle\Clockwork\Provider\ClockworkDataSourceProviderRegistry;

final class ClockworkMiddleware implements MiddlewareInterface
{

	public const StartTimeBag = 'clockwork.start_time';
	private const AuthUri = '#/__clockwork/auth#';
	private const DataUri = '#/__clockwork(?:/(?<id>([0-9-]+|latest)))?(?:/(?<direction>(?:previous|next)))?(?:/(?<count>\d+))?#';

	private readonly Clockwork $clockwork;

	public function __construct(
		private readonly DirectoriesInterface $directories,
		private readonly ScopeInterface $scope,
		private readonly ClockworkDataSourceProviderRegistry $providers,
		private readonly EnvironmentInterface $environment,
	)
	{
		$this->clockwork = $this->createDefaultClockwork();
	}

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		if ($this->environment->get('CLOCKWORK_ENABLED', false) !== true) {
			return $handler->handle($request);
		}

		if (preg_match(self::AuthUri, $request->getUri()->getPath())) {
			return $this->authenticate($request);
		}

		if (preg_match(self::DataUri, $request->getUri()->getPath(), $matches)) {
			$matches = array_merge([ 'id' => null, 'direction' => null, 'count' => null ], $matches);

			return $this->retrieveRequest($request, $matches['id'], $matches['direction'], $matches['count']);
		}

		$startTime = $request->getAttribute(self::StartTimeBag);

		if (is_numeric($startTime)) {
			$startTime = (float) $startTime;
		} else {
			$startTime = microtime(true);
		}

		ClockworkGlobalLogger::instance($this->clockwork);

		$response = $this->scope->runScope([
			Clockwork::class => $this->clockwork,
		], function (Container $container) use ($request, $handler) {
			if ($container->has(ListenerRegistryInterface::class)) {
				/** @var ListenerRegistryInterface $registry */
				$registry = $container->get(ListenerRegistryInterface::class);
				/** @var ListenerFactoryInterface $factory */
				$factory = $container->get(ListenerFactoryInterface::class);

				$registry->addListener(MiddlewareProcessing::class, $factory->create(ClockworkMiddlewareListener::class, '__invoke'));
			}

			return $handler->handle($request);
		});

		return $this->logRequest($request, $response, $startTime);
	}

	private function authenticate(ServerRequestInterface $request): ResponseInterface
	{
		$data = $request->getParsedBody();
		$token = $this->clockwork->authenticator()->attempt(is_array($data) ? $data : []);

		return $this->jsonResponse(['token' => $token], $token ? 200 : 403);
	}

	private function createDefaultClockwork(): Clockwork
	{
		$clockwork = new Clockwork();

		$storagePath = $this->directories->get('runtime') . '/clockwork';

		$clockwork->storage(new FileStorage($storagePath));
		$clockwork->authenticator(new NullAuthenticator);

		return $clockwork;
	}

	/**
	 * @param mixed[] $data
	 */
	private function jsonResponse(?array $data, int $status = 200): ResponseInterface
	{
		return new Response($status, ['Content-Type' => 'application/json'], $data === null ? null : json_encode($data, JSON_THROW_ON_ERROR));
	}

	private function retrieveRequest(ServerRequestInterface $request, ?string $id, ?string $direction, ?string $count): ResponseInterface
	{
		/** @var AuthenticatorInterface $authenticator */
		$authenticator = $this->clockwork->authenticator();
		/** @var StorageInterface $storage */
		$storage = $this->clockwork->storage();

		$authenticated = $authenticator->check(current($request->getHeader('X-Clockwork-Auth')));

		if ($authenticated !== true) {
			return $this->jsonResponse(['message' => $authenticated, 'requires' => $authenticator->requires()], 403);
		}

		if ($direction == 'previous') {
			/** @var Request $data */
			$data = $storage->previous($id, $count);
		} elseif ($direction == 'next') {
			/** @var Request $data */
			$data = $storage->next($id, $count);
		} elseif ($id == 'latest') {
			/** @var Request $data */
			$data = $storage->latest();
		} else {
			/** @var Request|null $data */
			$data = $storage->find($id);
		}

		return $this->jsonResponse($data?->toArray());
	}

	private function logRequest(ServerRequestInterface $request, ResponseInterface $response, float $startTime): ResponseInterface
	{
		$this->clockwork->timeline()->finalize($startTime);
		$this->clockwork->addDataSource(new PsrMessageDataSource($request, $response));
		$this->providers->injectTo($this->clockwork);

		/** @var Request $clockworkRequest */
		$clockworkRequest = $this->clockwork->request();

		$clockworkRequest->memoryUsage = memory_get_peak_usage(true);

		$this->clockwork->resolveRequest();
		$this->clockwork->storeRequest();

		/** @var Request $clockworkRequest */
		$clockworkRequest = $this->clockwork->request();

		$response = $response
			->withHeader('X-Clockwork-Id', $clockworkRequest->id)
			->withHeader('X-Clockwork-Version', Clockwork::VERSION);

		return $response->withHeader('Server-Timing', ServerTiming::fromRequest($clockworkRequest)->value());
	}

}
