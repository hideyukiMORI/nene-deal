<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneDeal\Auth\RequireRoleMiddleware;
use NeneDeal\User\OperatorRole;
use NeneDeal\Deal\DealRepositoryInterface;
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
                BuildBoardUseCase::class,
                static function (ContainerInterface $c): BuildBoardUseCase {
                    $stages = $c->get(PipelineStageRepositoryInterface::class);
                    $deals = $c->get(DealRepositoryInterface::class);

                    if (!$stages instanceof PipelineStageRepositoryInterface) {
                        throw new LogicException('Pipeline stage repository service is invalid.');
                    }

                    if (!$deals instanceof DealRepositoryInterface) {
                        throw new LogicException('Deal repository service is invalid.');
                    }

                    return new BuildBoardUseCase($stages, $deals);
                },
            )
            ->set(
                GetBoardHandler::class,
                static function (ContainerInterface $c): GetBoardHandler {
                    $useCase = $c->get(BuildBoardUseCase::class);
                    $json = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof BuildBoardUseCase) {
                        throw new LogicException('Build board use case service is invalid.');
                    }

                    if (!$json instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new GetBoardHandler($useCase, $json);
                },
            )
            ->set(
                CreateStageUseCase::class,
                static function (ContainerInterface $c): CreateStageUseCase {
                    $stages = $c->get(PipelineStageRepositoryInterface::class);
                    $org = $c->get(CurrentOrganization::class);

                    if (!$stages instanceof PipelineStageRepositoryInterface) {
                        throw new LogicException('Pipeline stage repository service is invalid.');
                    }

                    if (!$org instanceof CurrentOrganization) {
                        throw new LogicException('Current organization service is invalid.');
                    }

                    return new CreateStageUseCase($stages, $org);
                },
            )
            ->set(
                UpdateStageUseCase::class,
                static function (ContainerInterface $c): UpdateStageUseCase {
                    $stages = $c->get(PipelineStageRepositoryInterface::class);

                    if (!$stages instanceof PipelineStageRepositoryInterface) {
                        throw new LogicException('Pipeline stage repository service is invalid.');
                    }

                    return new UpdateStageUseCase($stages);
                },
            )
            ->set(
                DeleteStageUseCase::class,
                static function (ContainerInterface $c): DeleteStageUseCase {
                    $stages = $c->get(PipelineStageRepositoryInterface::class);

                    if (!$stages instanceof PipelineStageRepositoryInterface) {
                        throw new LogicException('Pipeline stage repository service is invalid.');
                    }

                    return new DeleteStageUseCase($stages);
                },
            )
            ->set(
                CreateStageHandler::class,
                static function (ContainerInterface $c): CreateStageHandler {
                    $useCase = $c->get(CreateStageUseCase::class);
                    $json = $c->get(JsonResponseFactory::class);
                    $problem = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$useCase instanceof CreateStageUseCase) {
                        throw new LogicException('Create stage use case service is invalid.');
                    }

                    if (!$json instanceof JsonResponseFactory || !$problem instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Response factory services are invalid.');
                    }

                    return new CreateStageHandler($useCase, $json, $problem);
                },
            )
            ->set(
                UpdateStageHandler::class,
                static function (ContainerInterface $c): UpdateStageHandler {
                    $useCase = $c->get(UpdateStageUseCase::class);
                    $json = $c->get(JsonResponseFactory::class);
                    $problem = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$useCase instanceof UpdateStageUseCase) {
                        throw new LogicException('Update stage use case service is invalid.');
                    }

                    if (!$json instanceof JsonResponseFactory || !$problem instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Response factory services are invalid.');
                    }

                    return new UpdateStageHandler($useCase, $json, $problem);
                },
            )
            ->set(
                DeleteStageHandler::class,
                static function (ContainerInterface $c): DeleteStageHandler {
                    $useCase = $c->get(DeleteStageUseCase::class);
                    $json = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof DeleteStageUseCase) {
                        throw new LogicException('Delete stage use case service is invalid.');
                    }

                    if (!$json instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new DeleteStageHandler($useCase, $json);
                },
            )
            ->set(
                PipelineStageRouteRegistrar::class,
                static function (ContainerInterface $c): PipelineStageRouteRegistrar {
                    $list = $c->get(ListStagesHandler::class);
                    $create = $c->get(CreateStageHandler::class);
                    $update = $c->get(UpdateStageHandler::class);
                    $delete = $c->get(DeleteStageHandler::class);
                    $board = $c->get(GetBoardHandler::class);
                    $problem = $c->get(ProblemDetailsResponseFactory::class);

                    if (!$list instanceof ListStagesHandler
                        || !$create instanceof CreateStageHandler
                        || !$update instanceof UpdateStageHandler
                        || !$delete instanceof DeleteStageHandler
                        || !$board instanceof GetBoardHandler
                        || !$problem instanceof ProblemDetailsResponseFactory
                    ) {
                        throw new LogicException('Pipeline handler or factory services are invalid.');
                    }

                    return new PipelineStageRouteRegistrar(
                        $list,
                        $create,
                        $update,
                        $delete,
                        $board,
                        new RequireRoleMiddleware(OperatorRole::Admin, $problem),
                    );
                },
            );

        // Register new exception handlers into the global list via ApplicationServiceProvider.
        $builder
            ->set(StageNotFoundExceptionHandler::class, static function (ContainerInterface $c): StageNotFoundExceptionHandler {
                $p = $c->get(ProblemDetailsResponseFactory::class);

                if (!$p instanceof ProblemDetailsResponseFactory) {
                    throw new LogicException('Problem details factory service is invalid.');
                }

                return new StageNotFoundExceptionHandler($p);
            })
            ->set(StageHasDealsExceptionHandler::class, static function (ContainerInterface $c): StageHasDealsExceptionHandler {
                $p = $c->get(ProblemDetailsResponseFactory::class);

                if (!$p instanceof ProblemDetailsResponseFactory) {
                    throw new LogicException('Problem details factory service is invalid.');
                }

                return new StageHasDealsExceptionHandler($p);
            })
            ->set(StageDeletionForbiddenExceptionHandler::class, static function (ContainerInterface $c): StageDeletionForbiddenExceptionHandler {
                $p = $c->get(ProblemDetailsResponseFactory::class);

                if (!$p instanceof ProblemDetailsResponseFactory) {
                    throw new LogicException('Problem details factory service is invalid.');
                }

                return new StageDeletionForbiddenExceptionHandler($p);
            })
            ->set(SlugAlreadyTakenExceptionHandler::class, static function (ContainerInterface $c): SlugAlreadyTakenExceptionHandler {
                $p = $c->get(ProblemDetailsResponseFactory::class);

                if (!$p instanceof ProblemDetailsResponseFactory) {
                    throw new LogicException('Problem details factory service is invalid.');
                }

                return new SlugAlreadyTakenExceptionHandler($p);
            });
    }
}
