<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

use LogicException;
use Nene2\Audit\AuditRecorderInterface;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\ClockInterface;
use Nene2\Http\JsonResponseFactory;
use NeneDeal\Pipeline\PipelineStageRepositoryInterface;
use NeneDeal\Tenancy\CurrentOrganization;
use Psr\Container\ContainerInterface;

/**
 * Wires the Deal domain: repository, use cases, handlers, domain exception
 * handlers, and the route registrar.
 */
final readonly class DealServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                DealRepositoryInterface::class,
                static function (ContainerInterface $c): DealRepositoryInterface {
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

                    return new PdoDealRepository($query, $org, $clock);
                },
            )
            ->set(CreateDealUseCase::class, static fn (ContainerInterface $c): CreateDealUseCase => new CreateDealUseCase(self::deals($c), self::stages($c), self::audit($c), self::org($c)))
            ->set(ListDealsUseCase::class, static fn (ContainerInterface $c): ListDealsUseCase => new ListDealsUseCase(self::deals($c)))
            ->set(GetDealUseCase::class, static fn (ContainerInterface $c): GetDealUseCase => new GetDealUseCase(self::deals($c)))
            ->set(UpdateDealUseCase::class, static fn (ContainerInterface $c): UpdateDealUseCase => new UpdateDealUseCase(self::deals($c), self::audit($c), self::org($c)))
            ->set(DeleteDealUseCase::class, static fn (ContainerInterface $c): DeleteDealUseCase => new DeleteDealUseCase(self::deals($c), self::audit($c), self::org($c)))
            ->set(RestoreDealUseCase::class, static fn (ContainerInterface $c): RestoreDealUseCase => new RestoreDealUseCase(self::deals($c), self::audit($c), self::org($c)))
            ->set(ChangeDealStageUseCase::class, static fn (ContainerInterface $c): ChangeDealStageUseCase => new ChangeDealStageUseCase(self::deals($c), self::stages($c), self::audit($c), self::org($c)))
            ->set(ListDealHistoryUseCase::class, static fn (ContainerInterface $c): ListDealHistoryUseCase => new ListDealHistoryUseCase(self::deals($c)))
            ->set(
                ListDealsHandler::class,
                static fn (ContainerInterface $c): ListDealsHandler => new ListDealsHandler(self::listUseCase($c), self::json($c)),
            )
            ->set(
                CreateDealHandler::class,
                static fn (ContainerInterface $c): CreateDealHandler => new CreateDealHandler(self::createUseCase($c), self::json($c), self::problem($c)),
            )
            ->set(
                GetDealHandler::class,
                static fn (ContainerInterface $c): GetDealHandler => new GetDealHandler(self::getUseCase($c), self::json($c)),
            )
            ->set(
                UpdateDealHandler::class,
                static fn (ContainerInterface $c): UpdateDealHandler => new UpdateDealHandler(self::updateUseCase($c), self::json($c), self::problem($c)),
            )
            ->set(
                DeleteDealHandler::class,
                static fn (ContainerInterface $c): DeleteDealHandler => new DeleteDealHandler(self::deleteUseCase($c), self::json($c)),
            )
            ->set(
                RestoreDealHandler::class,
                static fn (ContainerInterface $c): RestoreDealHandler => new RestoreDealHandler(self::restoreUseCase($c), self::json($c)),
            )
            ->set(
                ChangeDealStageHandler::class,
                static fn (ContainerInterface $c): ChangeDealStageHandler => new ChangeDealStageHandler(self::stageUseCase($c), self::json($c), self::problem($c)),
            )
            ->set(
                ListDealHistoryHandler::class,
                static fn (ContainerInterface $c): ListDealHistoryHandler => new ListDealHistoryHandler(self::historyUseCase($c), self::json($c)),
            )
            ->set(
                DealNotFoundExceptionHandler::class,
                static fn (ContainerInterface $c): DealNotFoundExceptionHandler => new DealNotFoundExceptionHandler(self::problem($c)),
            )
            ->set(
                UnknownStageExceptionHandler::class,
                static fn (ContainerInterface $c): UnknownStageExceptionHandler => new UnknownStageExceptionHandler(self::problem($c)),
            )
            ->set(
                DealRouteRegistrar::class,
                static function (ContainerInterface $c): DealRouteRegistrar {
                    $list = $c->get(ListDealsHandler::class);
                    $create = $c->get(CreateDealHandler::class);
                    $get = $c->get(GetDealHandler::class);
                    $update = $c->get(UpdateDealHandler::class);
                    $delete = $c->get(DeleteDealHandler::class);
                    $stageChange = $c->get(ChangeDealStageHandler::class);
                    $history = $c->get(ListDealHistoryHandler::class);
                    $restore = $c->get(RestoreDealHandler::class);

                    if (!$list instanceof ListDealsHandler
                        || !$create instanceof CreateDealHandler
                        || !$get instanceof GetDealHandler
                        || !$update instanceof UpdateDealHandler
                        || !$delete instanceof DeleteDealHandler
                        || !$stageChange instanceof ChangeDealStageHandler
                        || !$history instanceof ListDealHistoryHandler
                        || !$restore instanceof RestoreDealHandler
                    ) {
                        throw new LogicException('Deal handler services are invalid.');
                    }

                    return new DealRouteRegistrar($list, $create, $get, $update, $delete, $stageChange, $history, $restore);
                },
            );
    }

    private static function deals(ContainerInterface $c): DealRepositoryInterface
    {
        $repo = $c->get(DealRepositoryInterface::class);

        if (!$repo instanceof DealRepositoryInterface) {
            throw new LogicException('Deal repository service is invalid.');
        }

        return $repo;
    }

    private static function stages(ContainerInterface $c): PipelineStageRepositoryInterface
    {
        $repo = $c->get(PipelineStageRepositoryInterface::class);

        if (!$repo instanceof PipelineStageRepositoryInterface) {
            throw new LogicException('Pipeline stage repository service is invalid.');
        }

        return $repo;
    }

    private static function audit(ContainerInterface $c): AuditRecorderInterface
    {
        $audit = $c->get(AuditRecorderInterface::class);

        if (!$audit instanceof AuditRecorderInterface) {
            throw new LogicException('Audit recorder service is invalid.');
        }

        return $audit;
    }

    private static function org(ContainerInterface $c): CurrentOrganization
    {
        $org = $c->get(CurrentOrganization::class);

        if (!$org instanceof CurrentOrganization) {
            throw new LogicException('Current organization service is invalid.');
        }

        return $org;
    }

    private static function createUseCase(ContainerInterface $c): CreateDealUseCase
    {
        $u = $c->get(CreateDealUseCase::class);

        if (!$u instanceof CreateDealUseCase) {
            throw new LogicException('Create deal use case service is invalid.');
        }

        return $u;
    }

    private static function listUseCase(ContainerInterface $c): ListDealsUseCase
    {
        $u = $c->get(ListDealsUseCase::class);

        if (!$u instanceof ListDealsUseCase) {
            throw new LogicException('List deals use case service is invalid.');
        }

        return $u;
    }

    private static function getUseCase(ContainerInterface $c): GetDealUseCase
    {
        $u = $c->get(GetDealUseCase::class);

        if (!$u instanceof GetDealUseCase) {
            throw new LogicException('Get deal use case service is invalid.');
        }

        return $u;
    }

    private static function updateUseCase(ContainerInterface $c): UpdateDealUseCase
    {
        $u = $c->get(UpdateDealUseCase::class);

        if (!$u instanceof UpdateDealUseCase) {
            throw new LogicException('Update deal use case service is invalid.');
        }

        return $u;
    }

    private static function deleteUseCase(ContainerInterface $c): DeleteDealUseCase
    {
        $u = $c->get(DeleteDealUseCase::class);

        if (!$u instanceof DeleteDealUseCase) {
            throw new LogicException('Delete deal use case service is invalid.');
        }

        return $u;
    }

    private static function restoreUseCase(ContainerInterface $c): RestoreDealUseCase
    {
        $u = $c->get(RestoreDealUseCase::class);

        if (!$u instanceof RestoreDealUseCase) {
            throw new LogicException('Restore deal use case service is invalid.');
        }

        return $u;
    }

    private static function stageUseCase(ContainerInterface $c): ChangeDealStageUseCase
    {
        $u = $c->get(ChangeDealStageUseCase::class);

        if (!$u instanceof ChangeDealStageUseCase) {
            throw new LogicException('Change deal stage use case service is invalid.');
        }

        return $u;
    }

    private static function historyUseCase(ContainerInterface $c): ListDealHistoryUseCase
    {
        $u = $c->get(ListDealHistoryUseCase::class);

        if (!$u instanceof ListDealHistoryUseCase) {
            throw new LogicException('List deal history use case service is invalid.');
        }

        return $u;
    }

    private static function json(ContainerInterface $c): JsonResponseFactory
    {
        $j = $c->get(JsonResponseFactory::class);

        if (!$j instanceof JsonResponseFactory) {
            throw new LogicException('JSON response factory service is invalid.');
        }

        return $j;
    }

    private static function problem(ContainerInterface $c): ProblemDetailsResponseFactory
    {
        $p = $c->get(ProblemDetailsResponseFactory::class);

        if (!$p instanceof ProblemDetailsResponseFactory) {
            throw new LogicException('Problem details response factory service is invalid.');
        }

        return $p;
    }
}
