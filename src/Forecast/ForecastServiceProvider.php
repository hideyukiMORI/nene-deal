<?php

declare(strict_types=1);

namespace NeneDeal\Forecast;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\ClockInterface;
use Nene2\Http\JsonResponseFactory;
use NeneDeal\Deal\DealRepositoryInterface;
use NeneDeal\Pipeline\PipelineStageRepositoryInterface;
use NeneDeal\Settings\OrganizationSettingsRepositoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Wires the Forecast read model: use case, handler, and route registrar.
 * Reuses the Deal and Pipeline repositories registered by their providers.
 */
final readonly class ForecastServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                ComputeForecastUseCase::class,
                static function (ContainerInterface $c): ComputeForecastUseCase {
                    $stages = $c->get(PipelineStageRepositoryInterface::class);
                    $deals = $c->get(DealRepositoryInterface::class);

                    if (!$stages instanceof PipelineStageRepositoryInterface) {
                        throw new LogicException('Pipeline stage repository service is invalid.');
                    }

                    if (!$deals instanceof DealRepositoryInterface) {
                        throw new LogicException('Deal repository service is invalid.');
                    }

                    return new ComputeForecastUseCase($stages, $deals);
                },
            )
            ->set(
                GetForecastHandler::class,
                static function (ContainerInterface $c): GetForecastHandler {
                    $useCase = $c->get(ComputeForecastUseCase::class);
                    $settings = $c->get(OrganizationSettingsRepositoryInterface::class);
                    $json = $c->get(JsonResponseFactory::class);
                    $problem = $c->get(ProblemDetailsResponseFactory::class);
                    $clock = $c->get(ClockInterface::class);

                    if (!$useCase instanceof ComputeForecastUseCase) {
                        throw new LogicException('Compute forecast use case service is invalid.');
                    }

                    if (!$settings instanceof OrganizationSettingsRepositoryInterface) {
                        throw new LogicException('Organization settings repository service is invalid.');
                    }

                    if (!$json instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    if (!$problem instanceof ProblemDetailsResponseFactory) {
                        throw new LogicException('Problem details response factory service is invalid.');
                    }

                    if (!$clock instanceof ClockInterface) {
                        throw new LogicException('Clock service is invalid.');
                    }

                    return new GetForecastHandler($useCase, $settings, $json, $problem, $clock);
                },
            )
            ->set(
                ForecastRouteRegistrar::class,
                static function (ContainerInterface $c): ForecastRouteRegistrar {
                    $handler = $c->get(GetForecastHandler::class);

                    if (!$handler instanceof GetForecastHandler) {
                        throw new LogicException('Get forecast handler service is invalid.');
                    }

                    return new ForecastRouteRegistrar($handler);
                },
            );
    }
}
