<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Http\JsonResponseFactory;
use NeneDeal\Tenancy\CurrentOrganization;
use Psr\Container\ContainerInterface;

/**
 * Wires the Pipeline (stages) domain: repository, list use case, handler, and
 * route registrar.
 */
final readonly class PipelineServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                PipelineStageRepositoryInterface::class,
                static function (ContainerInterface $c): PipelineStageRepositoryInterface {
                    $query = $c->get(DatabaseQueryExecutorInterface::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    $org = $c->get(CurrentOrganization::class);

                    if (!$org instanceof CurrentOrganization) {
                        throw new LogicException('Current organization service is invalid.');
                    }

                    return new PdoPipelineStageRepository($query, $org);
                },
            )
            ->set(
                ListStagesUseCase::class,
                static function (ContainerInterface $c): ListStagesUseCase {
                    $repo = $c->get(PipelineStageRepositoryInterface::class);

                    if (!$repo instanceof PipelineStageRepositoryInterface) {
                        throw new LogicException('Pipeline stage repository service is invalid.');
                    }

                    return new ListStagesUseCase($repo);
                },
            )
            ->set(
                ListStagesHandler::class,
                static function (ContainerInterface $c): ListStagesHandler {
                    $useCase = $c->get(ListStagesUseCase::class);
                    $json = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof ListStagesUseCase) {
                        throw new LogicException('List stages use case service is invalid.');
                    }

                    if (!$json instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new ListStagesHandler($useCase, $json);
                },
            )
            ->set(
                PipelineStageRouteRegistrar::class,
                static function (ContainerInterface $c): PipelineStageRouteRegistrar {
                    $list = $c->get(ListStagesHandler::class);

                    if (!$list instanceof ListStagesHandler) {
                        throw new LogicException('List stages handler service is invalid.');
                    }

                    return new PipelineStageRouteRegistrar($list);
                },
            );
    }
}
