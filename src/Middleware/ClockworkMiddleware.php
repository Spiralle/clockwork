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
use Spiralle\Clockwork\Log\ClockworkGlobalLogger;
use Spiralle\Clockwork\Provider\ClockworkDataSourceProviderRegistry;

final class ClockworkMiddleware implements MiddlewareInterface
{

	public const Attribute = 'clockwork';
	public const StartTimeBag = 'clockwork.start_time';

	private const AuthUri = '#/__clockwork/auth#';
	private const DataUri = '#/__clockwork(?:/(?<id>([0-9-]+|latest)))?(?:/(?<direction>(?:previous|next)))?(?:/(?<count>\d+))?#';

	public function __construct(
		private readonly DirectoriesInterface $directories,
		private readonly ClockworkDataSourceProviderRegistry $providers,
		private readonly EnvironmentInterface $environment,
	)
	{
	}

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		if ($this->environment->get('CLOCKWORK_ENABLED', false) !== true) {
			return $handler->handle($request);
		}

		$clockwork = $this->createDefaultClockwork();

		if (preg_match(self::AuthUri, $request->getUri()->getPath())) {
			return $this->authenticate($clockwork, $request);
		}

		if (preg_match(self::DataUri, $request->getUri()->getPath(), $matches)) {
			$matches = array_merge([ 'id' => null, 'direction' => null, 'count' => null ], $matches);

			return $this->retrieveRequest($clockwork, $request, $matches['id'], $matches['direction'], $matches['count']);
		}

		$startTime = $request->getAttribute(self::StartTimeBag);

		if (is_numeric($startTime)) {
			$startTime = (float) $startTime;
		} else {
			$startTime = microtime(true);
		}

		ClockworkGlobalLogger::instance($clockwork);

		$response = $handler->handle($request);

		return $this->logRequest($clockwork, $request->withAttribute(self::Attribute, $clockwork), $response, $startTime);
	}

	private function authenticate(Clockwork $clockwork, ServerRequestInterface $request): ResponseInterface
	{
		$data = $request->getParsedBody();
		$token = $clockwork->authenticator()->attempt(is_array($data) ? $data : []);

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

	private function retrieveRequest(Clockwork $clockwork, ServerRequestInterface $request, ?string $id, ?string $direction, ?string $count): ResponseInterface
	{
		/** @var AuthenticatorInterface $authenticator */
		$authenticator = $clockwork->authenticator();
		/** @var StorageInterface $storage */
		$storage = $clockwork->storage();

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

	private function logRequest(Clockwork $clockwork, ServerRequestInterface $request, ResponseInterface $response, float $startTime): ResponseInterface
	{
		$clockwork->timeline()->finalize($startTime);
		$clockwork->addDataSource(new PsrMessageDataSource($request, $response));
		$this->providers->injectTo($clockwork);

		/** @var Request $clockworkRequest */
		$clockworkRequest = $clockwork->request();

		$clockworkRequest->memoryUsage = memory_get_peak_usage(true);

		$clockwork->resolveRequest();
		$clockwork->storeRequest();

		/** @var Request $clockworkRequest */
		$clockworkRequest = $clockwork->request();

		$response = $response
			->withHeader('X-Clockwork-Id', $clockworkRequest->id)
			->withHeader('X-Clockwork-Version', Clockwork::VERSION);

		return $response->withHeader('Server-Timing', ServerTiming::fromRequest($clockworkRequest)->value());
	}

}
