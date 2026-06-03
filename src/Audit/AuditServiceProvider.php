<?php

declare(strict_types=1);

namespace NeneDeal\Audit;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use NeneDeal\Auth\RequireRoleMiddleware;
use NeneDeal\Tenancy\CurrentOrganization;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;

/**
 * Wires the Audit domain: export repository, use case, CSV handler and the
 * admin-gated route registrar.
 */
final readonly class AuditServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                AuditExportRepositoryInterface::class,
                static function (ContainerInterface $c): AuditExportRepositoryInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);
                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    $org = $c->get(CurrentOrganization::class);
                    if (!$org instanceof CurrentOrganization) {
                        throw new LogicException('Current organization service is invalid.');
                    }

                    return new PdoAuditExportRepository($query, $org);
                },
            )
            ->set(
                ExportAuditUseCase::class,
                static function (ContainerInterface $c): ExportAuditUseCase {
                    $repo = $c->get(AuditExportRepositoryInterface::class);
                    if (!$repo instanceof AuditExportRepositoryInterface) {
                        throw new LogicException('Audit export repository service is invalid.');
                    }

                    return new ExportAuditUseCase($repo);
                },
            )
            ->set(
                AuditCsvHandler::class,
                static function (ContainerInterface $c): AuditCsvHandler {
                    $useCase = $c->get(ExportAuditUseCase::class);
                    $psr17 = $c->get(Psr17Factory::class);
                    $problem = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$useCase instanceof ExportAuditUseCase
                        || !$psr17 instanceof Psr17Factory
                        || !$problem instanceof ProblemDetailsResponseFactory
                    ) {
                        throw new LogicException('Audit CSV handler dependencies are invalid.');
                    }

                    return new AuditCsvHandler($useCase, $psr17, $problem);
                },
            )
            ->set(
                AuditRouteRegistrar::class,
                static function (ContainerInterface $c): AuditRouteRegistrar {
                    $csv = $c->get(AuditCsvHandler::class);
                    $admin = $c->get(RequireRoleMiddleware::class);

                    if (!$csv instanceof AuditCsvHandler || !$admin instanceof RequireRoleMiddleware) {
                        throw new LogicException('Audit route registrar dependencies are invalid.');
                    }

                    return new AuditRouteRegistrar($csv, $admin);
                },
            );
    }
}
