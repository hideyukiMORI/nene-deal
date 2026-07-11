<?php

declare(strict_types=1);

namespace NeneDeal\Settings;

use LogicException;
use Nene2\Audit\AuditRecorderInterface;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Http\ClockInterface;
use Nene2\Http\JsonResponseFactory;
use NeneDeal\Auth\RequireRoleMiddleware;
use NeneDeal\Tenancy\CurrentOrganization;
use Psr\Container\ContainerInterface;

/**
 * Wires organization settings: repository, handlers, admin-gated routes.
 */
final readonly class SettingsServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                OrganizationSettingsRepositoryInterface::class,
                static function (ContainerInterface $c): OrganizationSettingsRepositoryInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);
                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    $org = $c->get(CurrentOrganization::class);
                    if (!$org instanceof CurrentOrganization) {
                        throw new LogicException('Current organization service is invalid.');
                    }

                    $clock = $c->get(ClockInterface::class);
                    if (!$clock instanceof ClockInterface) {
                        throw new LogicException('Clock service is invalid.');
                    }

                    return new PdoOrganizationSettingsRepository($query, $org, $clock);
                },
            )
            ->set(
                GetSettingsHandler::class,
                static function (ContainerInterface $c): GetSettingsHandler {
                    return new GetSettingsHandler(self::repo($c), self::json($c));
                },
            )
            ->set(
                UpdateSettingsHandler::class,
                static function (ContainerInterface $c): UpdateSettingsHandler {
                    $audit = $c->get(AuditRecorderInterface::class);
                    if (!$audit instanceof AuditRecorderInterface) {
                        throw new LogicException('Audit recorder service is invalid.');
                    }

                    $org = $c->get(CurrentOrganization::class);
                    if (!$org instanceof CurrentOrganization) {
                        throw new LogicException('Current organization service is invalid.');
                    }

                    return new UpdateSettingsHandler(self::repo($c), self::json($c), $audit, $org);
                },
            )
            ->set(
                SettingsRouteRegistrar::class,
                static function (ContainerInterface $c): SettingsRouteRegistrar {
                    $get = $c->get(GetSettingsHandler::class);
                    $update = $c->get(UpdateSettingsHandler::class);
                    $admin = $c->get(RequireRoleMiddleware::class);

                    if (!$get instanceof GetSettingsHandler
                        || !$update instanceof UpdateSettingsHandler
                        || !$admin instanceof RequireRoleMiddleware
                    ) {
                        throw new LogicException('Settings route registrar dependencies are invalid.');
                    }

                    return new SettingsRouteRegistrar($get, $update, $admin);
                },
            );
    }

    private static function repo(ContainerInterface $c): OrganizationSettingsRepositoryInterface
    {
        $repo = $c->get(OrganizationSettingsRepositoryInterface::class);
        if (!$repo instanceof OrganizationSettingsRepositoryInterface) {
            throw new LogicException('Organization settings repository service is invalid.');
        }

        return $repo;
    }

    private static function json(ContainerInterface $c): JsonResponseFactory
    {
        $json = $c->get(JsonResponseFactory::class);
        if (!$json instanceof JsonResponseFactory) {
            throw new LogicException('JSON response factory service is invalid.');
        }

        return $json;
    }
}
