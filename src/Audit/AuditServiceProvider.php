<?php

declare(strict_types=1);

namespace NeneDeal\Audit;

use LogicException;
use Nene2\Audit\AuditEventRepositoryInterface;
use Nene2\Audit\AuditRecorder;
use Nene2\Audit\AuditRecorderInterface;
use Nene2\Audit\AuditTableConfig;
use Nene2\Audit\PdoAuditEventRepository;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Http\ClockInterface;
use NeneDeal\Auth\RequireRoleMiddleware;
use NeneDeal\Tenancy\CurrentOrganization;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;

/**
 * Wires the Audit domain: the NENE2 `Nene2\Audit` consumer (append-only
 * `audit_events` recorder — hard rule: every mutation goes through
 * AuditRecorder, #89 / ADR 0005), plus the stage-history CSV export
 * (repository, use case, handler and the admin-gated route registrar).
 */
final readonly class AuditServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                AuditTableConfig::class,
                // Canonical NENE2 table shape; actor column is `actor_id` and
                // ids are ULID strings (see migration + ADR 0005/0006).
                static fn (): AuditTableConfig => AuditTableConfig::canonical(),
            )
            ->set(
                AuditEventRepositoryInterface::class,
                static function (ContainerInterface $c): AuditEventRepositoryInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);
                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    $config = $c->get(AuditTableConfig::class);
                    if (!$config instanceof AuditTableConfig) {
                        throw new LogicException('Audit table config service is invalid.');
                    }

                    return new PdoAuditEventRepository($query, $config);
                },
            )
            ->set(
                AuditRecorderInterface::class,
                static function (ContainerInterface $c): AuditRecorderInterface {
                    $repository = $c->get(AuditEventRepositoryInterface::class);
                    if (!$repository instanceof AuditEventRepositoryInterface) {
                        throw new LogicException('Audit event repository service is invalid.');
                    }

                    $clock = $c->get(ClockInterface::class);
                    if (!$clock instanceof ClockInterface) {
                        throw new LogicException('Clock service is invalid.');
                    }

                    // No org holder: NeneDeal's RequestScopedHolder<string> is not
                    // assignable to the framework's invariant RequestScopedHolder<string|int>
                    // — every call site sets organizationId explicitly on the AuditEvent.
                    return new AuditRecorder($repository, $clock);
                },
            )
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

                    if (!$useCase instanceof ExportAuditUseCase
                        || !$psr17 instanceof Psr17Factory
                    ) {
                        throw new LogicException('Audit CSV handler dependencies are invalid.');
                    }

                    return new AuditCsvHandler($useCase, $psr17);
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
