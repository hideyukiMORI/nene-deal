<?php

declare(strict_types=1);

namespace NeneDeal\Http;

use LogicException;
use Nene2\Auth\BearerTokenMiddleware;
use Nene2\Auth\GuardedJwtSecretResolver;
use Nene2\Auth\LocalBearerTokenVerifier;
use Nene2\Auth\TokenIssuerInterface;
use Nene2\Auth\TokenVerifierInterface;
use Nene2\Config\AppConfig;
use Nene2\Config\ConfigLoader;
use Nene2\Database\DatabaseConnectionFactoryInterface;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\ClockInterface;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RequestScopedHolder;
use Nene2\Http\ResponseEmitter;
use Nene2\Http\RuntimeApplicationFactory;
use Nene2\Http\UtcClock;
use Nene2\Routing\Router;
use NeneDeal\ApplicationServiceProvider;
use NeneDeal\Audit\AuditServiceProvider;
use NeneDeal\Auth\AuthServiceProvider;
use NeneDeal\Deal\DealServiceProvider;
use NeneDeal\Demo\DemoServiceProvider;
use NeneDeal\Forecast\ForecastServiceProvider;
use NeneDeal\Handoff\HandoffServiceProvider;
use NeneDeal\Pipeline\PipelineServiceProvider;
use NeneDeal\Settings\SettingsServiceProvider;
use NeneDeal\Tenancy\CurrentOrganization;
use NeneDeal\Tenancy\HolderCurrentOrganization;
use NeneDeal\Tenancy\OrganizationResolver;
use NeneDeal\Tenancy\PdoOrganizationResolver;
use NeneDeal\Tenancy\RequestOrganizationMiddleware;
use NeneDeal\User\UserServiceProvider;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Wires the NENE2 HTTP runtime for NeNe Deal: configuration, database
 * connectivity, the response factories, the (single-organization) tenancy
 * resolver, the domain providers, and the request handler.
 *
 * Tenant resolution runs as request middleware ({@see RequestOrganizationMiddleware})
 * that populates the request-scoped organization holder; mutating requests are
 * gated by the machine API key when `NENE_DEAL_API_KEY` is configured.
 */
final readonly class RuntimeServiceProvider implements ServiceProviderInterface
{
    public const PROJECT_ROOT = 'nene-deal.project_root';

    /** Container id for the request-scoped organization-id holder (string). */
    public const ORG_ID_HOLDER = 'nene-deal.org_id_holder';

    /**
     * Development-only fallback secret, injected into
     * {@see GuardedJwtSecretResolver::fromConfig()}. It is used **only** in
     * local/test, and only when the operator opts in via
     * `NENE2_ALLOW_DEV_SECRET=1` while NENE2_LOCAL_JWT_SECRET is unset;
     * production always rejects it. This constant is public in the OSS
     * repository, so signing real tokens with it would be a full auth bypass.
     */
    private const DEFAULT_DEV_SECRET = 'nene-deal-dev-secret';

    public function register(ContainerBuilder $builder): void
    {
        $builder->addProvider(new ApplicationServiceProvider());
        $builder->addProvider(new PipelineServiceProvider());
        $builder->addProvider(new DealServiceProvider());
        $builder->addProvider(new ForecastServiceProvider());
        $builder->addProvider(new HandoffServiceProvider());
        $builder->addProvider(new UserServiceProvider());
        $builder->addProvider(new AuthServiceProvider());
        $builder->addProvider(new AuditServiceProvider());
        $builder->addProvider(new SettingsServiceProvider());
        $builder->addProvider(new DemoServiceProvider());

        $builder
            ->set(Psr17Factory::class, static fn (ContainerInterface $container): Psr17Factory => new Psr17Factory())
            // Single time source for the whole application. Production resolves to
            // the real UTC clock (`UtcClock::now()` is equivalent to the raw
            // `time()`/`date()` reads it replaces); tests inject a fixed clock.
            ->set(ClockInterface::class, static fn (ContainerInterface $container): ClockInterface => new UtcClock())
            ->set(
                ConfigLoader::class,
                static function (ContainerInterface $container): ConfigLoader {
                    $projectRoot = $container->get(self::PROJECT_ROOT);

                    if (!is_string($projectRoot) || $projectRoot === '') {
                        throw new LogicException('Project root service is invalid.');
                    }

                    return new ConfigLoader($projectRoot);
                },
            )
            ->set(
                AppConfig::class,
                static function (ContainerInterface $container): AppConfig {
                    $loader = $container->get(ConfigLoader::class);

                    if (!$loader instanceof ConfigLoader) {
                        throw new LogicException('Config loader service is invalid.');
                    }

                    return $loader->load();
                },
            )
            ->set(
                DatabaseConnectionFactoryInterface::class,
                static function (ContainerInterface $container): DatabaseConnectionFactoryInterface {
                    $config = $container->get(AppConfig::class);

                    if (!$config instanceof AppConfig) {
                        throw new LogicException('Application config service is invalid.');
                    }

                    return new PdoConnectionFactory($config->database);
                },
            )
            ->set(
                DatabaseQueryExecutorInterface::class,
                static function (ContainerInterface $container): DatabaseQueryExecutorInterface {
                    $connectionFactory = $container->get(DatabaseConnectionFactoryInterface::class);

                    if (!$connectionFactory instanceof DatabaseConnectionFactoryInterface) {
                        throw new LogicException('Database connection factory service is invalid.');
                    }

                    return new PdoDatabaseQueryExecutor($connectionFactory);
                },
            )
            ->set(
                self::ORG_ID_HOLDER,
                static fn (ContainerInterface $container): RequestScopedHolder => new RequestScopedHolder(),
            )
            ->set(
                OrganizationResolver::class,
                static function (ContainerInterface $container): OrganizationResolver {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    return new PdoOrganizationResolver($query);
                },
            )
            ->set(
                CurrentOrganization::class,
                static function (ContainerInterface $container): CurrentOrganization {
                    $holder = $container->get(self::ORG_ID_HOLDER);

                    if (!$holder instanceof RequestScopedHolder) {
                        throw new LogicException('Organization id holder service is invalid.');
                    }

                    /** @var RequestScopedHolder<string> $holder */
                    return new HolderCurrentOrganization($holder);
                },
            )
            ->set(
                RequestOrganizationMiddleware::class,
                static function (ContainerInterface $container): RequestOrganizationMiddleware {
                    $holder = $container->get(self::ORG_ID_HOLDER);
                    $resolver = $container->get(OrganizationResolver::class);
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$holder instanceof RequestScopedHolder) {
                        throw new LogicException('Organization id holder service is invalid.');
                    }

                    if (!$resolver instanceof OrganizationResolver) {
                        throw new LogicException('Organization resolver service is invalid.');
                    }

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    /** @var RequestScopedHolder<string> $holder */
                    return new RequestOrganizationMiddleware($holder, $resolver, $problemDetails);
                },
            )
            ->set(
                DatabaseHealthCheck::class,
                static function (ContainerInterface $container): DatabaseHealthCheck {
                    $connectionFactory = $container->get(DatabaseConnectionFactoryInterface::class);

                    if (!$connectionFactory instanceof DatabaseConnectionFactoryInterface) {
                        throw new LogicException('Database connection factory service is invalid.');
                    }

                    return new DatabaseHealthCheck($connectionFactory);
                },
            )
            ->set(
                JsonResponseFactory::class,
                static function (ContainerInterface $container): JsonResponseFactory {
                    $psr17 = $container->get(Psr17Factory::class);

                    if (!$psr17 instanceof Psr17Factory) {
                        throw new LogicException('PSR-17 factory service is invalid.');
                    }

                    return new JsonResponseFactory($psr17, $psr17);
                },
            )
            ->set(
                ProblemDetailsResponseFactory::class,
                static function (ContainerInterface $container): ProblemDetailsResponseFactory {
                    $psr17 = $container->get(Psr17Factory::class);
                    $config = $container->get(AppConfig::class);

                    if (!$psr17 instanceof Psr17Factory) {
                        throw new LogicException('PSR-17 factory service is invalid.');
                    }

                    if (!$config instanceof AppConfig) {
                        throw new LogicException('Application config service is invalid.');
                    }

                    return new ProblemDetailsResponseFactory($psr17, $psr17, $config->problemDetailsBaseUrl);
                },
            )
            ->set(
                LocalBearerTokenVerifier::class,
                static function (ContainerInterface $container): LocalBearerTokenVerifier {
                    $config = $container->get(AppConfig::class);

                    if (!$config instanceof AppConfig) {
                        throw new LogicException('Application config service is invalid.');
                    }

                    // The framework resolver fails closed: in production an unset
                    // NENE2_LOCAL_JWT_SECRET refuses to boot rather than signing
                    // tokens with the public dev constant. Local/test may use the
                    // injected dev secret only behind the NENE2_ALLOW_DEV_SECRET
                    // opt-in, so login works without configuration once enabled.
                    return new LocalBearerTokenVerifier(
                        GuardedJwtSecretResolver::fromConfig($config, self::DEFAULT_DEV_SECRET),
                    );
                },
            )
            ->set(
                TokenIssuerInterface::class,
                static function (ContainerInterface $container): TokenIssuerInterface {
                    $verifier = $container->get(LocalBearerTokenVerifier::class);

                    if (!$verifier instanceof LocalBearerTokenVerifier) {
                        throw new LogicException('Local bearer token verifier service is invalid.');
                    }

                    return $verifier;
                },
            )
            ->set(
                TokenVerifierInterface::class,
                static function (ContainerInterface $container): TokenVerifierInterface {
                    $verifier = $container->get(LocalBearerTokenVerifier::class);

                    if (!$verifier instanceof LocalBearerTokenVerifier) {
                        throw new LogicException('Local bearer token verifier service is invalid.');
                    }

                    return $verifier;
                },
            )
            ->set(
                RuntimeApplicationFactory::class,
                static function (ContainerInterface $container): RuntimeApplicationFactory {
                    $psr17 = $container->get(Psr17Factory::class);

                    if (!$psr17 instanceof Psr17Factory) {
                        throw new LogicException('PSR-17 factory service is invalid.');
                    }

                    $config = $container->get(AppConfig::class);

                    if (!$config instanceof AppConfig) {
                        throw new LogicException('Application config service is invalid.');
                    }

                    $databaseHealthCheck = $container->get(DatabaseHealthCheck::class);

                    if (!$databaseHealthCheck instanceof DatabaseHealthCheck) {
                        throw new LogicException('Database health check service is invalid.');
                    }

                    $routeRegistrars = $container->get(ApplicationServiceProvider::ROUTE_REGISTRARS);

                    if (!is_array($routeRegistrars) || !array_is_list($routeRegistrars)) {
                        throw new LogicException('Route registrars service is invalid.');
                    }

                    /** @var list<callable(Router): void> $routeRegistrars */
                    $exceptionHandlers = $container->get(ApplicationServiceProvider::EXCEPTION_HANDLERS);

                    if (!is_array($exceptionHandlers) || !array_is_list($exceptionHandlers)) {
                        throw new LogicException('Exception handlers service is invalid.');
                    }

                    /** @var list<DomainExceptionHandlerInterface> $exceptionHandlers */

                    $orgMiddleware = $container->get(RequestOrganizationMiddleware::class);

                    if (!$orgMiddleware instanceof RequestOrganizationMiddleware) {
                        throw new LogicException('Request organization middleware service is invalid.');
                    }

                    // Operator JWT: the bearer middleware is always part of the
                    // chain, protecting every route except /health, /, the public
                    // login endpoint and the public demo start (#69; the handler
                    // itself is 404 fail-closed while DEMO_MODE is off). The secret
                    // presence no longer gates the auth stage — the verifier's
                    // secret fails closed in production and uses the injected dev
                    // secret in local/test (only behind the NENE2_ALLOW_DEV_SECRET
                    // opt-in, see GuardedJwtSecretResolver), so an unset secret can
                    // never silently drop operator gating.
                    $verifier = $container->get(TokenVerifierInterface::class);
                    $problemDetails = $container->get(ProblemDetailsResponseFactory::class);

                    if (!$verifier instanceof TokenVerifierInterface) {
                        throw new LogicException('Token verifier service is invalid.');
                    }

                    if (!$problemDetails instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    // Bearer runs BEFORE org resolution: the org middleware reads
                    // the verified `org` claim (authoritative tenant binding —
                    // required once disposable demo orgs make organizations plural,
                    // because the header-less SPA can no longer rely on the
                    // sole-org fallback).
                    $authMiddlewares = [
                        new BearerTokenMiddleware(
                            $problemDetails,
                            $verifier,
                            excludedPaths: ['/', '/health', '/api/v1/auth/login', '/demo/standard'],
                        ),
                        $orgMiddleware,
                    ];

                    // Machine API key gates mutating requests when configured.
                    // When unset (dev), keep the API open: the framework's
                    // api-key middleware is always present and fails closed, so
                    // restrict its allowlist to an unused path rather than the
                    // "protect all" default. Reads stay public either way.
                    $apiKey = self::env('NENE_DEAL_API_KEY');
                    $gated = $apiKey !== null;

                    return new RuntimeApplicationFactory(
                        responseFactory: $psr17,
                        streamFactory: $psr17,
                        machineApiKey: $apiKey,
                        domainExceptionHandlers: $exceptionHandlers,
                        routeRegistrars: $routeRegistrars,
                        authMiddleware: $authMiddlewares,
                        healthChecks: [$databaseHealthCheck],
                        debug: $config->debug,
                        // Opt-in の X-Authorization フォールバック受け口（NENE2 #1558・ADR 0019）。
                        // 前段 proxy が標準 Authorization を PHP に渡す前に剥がす共有ホスティング
                        // （HETEML 型 Tier A — custom ヘッダは通るが Authorization は通らず、
                        // `.htaccess` 側の対策も `CGIPassAuth` も効かないことを invoice #597 /
                        // vault #118 で確認済み）向け。SPA が全リクエストに付与する
                        // `X-Authorization: Bearer` ミラーを、Authorization 不在/空のときのみ
                        // 採用する。標準ヘッダが届く環境ではバイト不変。
                        enableAuthorizationHeaderFallback: true,
                        machineApiKeyProtectedPaths: $gated ? [] : ['/machine/health'],
                        machineApiKeyProtectedMethods: $gated ? ['POST', 'PATCH', 'PUT', 'DELETE'] : [],
                    );
                },
            )
            ->set(
                RequestHandlerInterface::class,
                static function (ContainerInterface $container): RequestHandlerInterface {
                    $factory = $container->get(RuntimeApplicationFactory::class);

                    if (!$factory instanceof RuntimeApplicationFactory) {
                        throw new LogicException('Runtime application factory service is invalid.');
                    }

                    return $factory->create();
                },
            )
            ->set(ResponseEmitter::class, static fn (ContainerInterface $container): ResponseEmitter => new ResponseEmitter());
    }

    /** Reads an environment variable, returning null when unset or empty. */
    private static function env(string $key): ?string
    {
        $value = $_SERVER[$key] ?? $_ENV[$key] ?? getenv($key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
