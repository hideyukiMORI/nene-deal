<?php

declare(strict_types=1);

namespace NeneDeal;

use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

/**
 * Aggregates the application's route registrars and domain exception handlers.
 *
 * `GET /` and `GET /health` are provided by the framework runtime. Per-domain
 * providers (Deal, Pipeline, …) will register their route registrars in the
 * container in later issues; this provider collects them into the lists the
 * runtime consumes. The scaffold ships with empty lists.
 */
final readonly class ApplicationServiceProvider implements ServiceProviderInterface
{
    public const ROUTE_REGISTRARS = 'nene-deal.route_registrars';
    public const EXCEPTION_HANDLERS = 'nene-deal.exception_handlers';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(self::ROUTE_REGISTRARS, static fn (ContainerInterface $container): array => [])
            ->set(self::EXCEPTION_HANDLERS, static fn (ContainerInterface $container): array => []);
    }
}
