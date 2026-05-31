<?php

declare(strict_types=1);

namespace NeneDeal;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use NeneDeal\Auth\AuthRouteRegistrar;
use NeneDeal\Auth\InvalidCredentialsExceptionHandler;
use NeneDeal\Deal\DealNotFoundExceptionHandler;
use NeneDeal\Deal\DealRouteRegistrar;
use NeneDeal\Deal\UnknownStageExceptionHandler;
use NeneDeal\Forecast\ForecastRouteRegistrar;
use NeneDeal\Handoff\AlreadyHandedOffExceptionHandler;
use NeneDeal\Handoff\HandoffPreconditionExceptionHandler;
use NeneDeal\Handoff\InvoiceHandoffExceptionHandler;
use NeneDeal\Handoff\InvoiceHandoffRouteRegistrar;
use NeneDeal\Pipeline\PipelineStageRouteRegistrar;
use NeneDeal\Pipeline\SlugAlreadyTakenExceptionHandler;
use NeneDeal\Pipeline\StageDeletionForbiddenExceptionHandler;
use NeneDeal\Pipeline\StageHasDealsExceptionHandler;
use NeneDeal\Pipeline\StageNotFoundExceptionHandler;
use NeneDeal\User\CannotModifySelfExceptionHandler;
use NeneDeal\User\EmailAlreadyTakenExceptionHandler;
use NeneDeal\User\UserNotFoundExceptionHandler;
use NeneDeal\User\UserRouteRegistrar;
use Psr\Container\ContainerInterface;

/**
 * Aggregates the application's route registrars and domain exception handlers
 * into the lists the runtime consumes. `GET /` and `GET /health` are provided
 * by the framework runtime.
 */
final readonly class ApplicationServiceProvider implements ServiceProviderInterface
{
    public const ROUTE_REGISTRARS = 'nene-deal.route_registrars';
    public const EXCEPTION_HANDLERS = 'nene-deal.exception_handlers';

    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                self::ROUTE_REGISTRARS,
                static function (ContainerInterface $container): array {
                    $stages = $container->get(PipelineStageRouteRegistrar::class);
                    $deals = $container->get(DealRouteRegistrar::class);
                    $forecast = $container->get(ForecastRouteRegistrar::class);
                    $handoff = $container->get(InvoiceHandoffRouteRegistrar::class);
                    $auth = $container->get(AuthRouteRegistrar::class);
                    $users = $container->get(UserRouteRegistrar::class);

                    if (!$stages instanceof PipelineStageRouteRegistrar
                        || !$deals instanceof DealRouteRegistrar
                        || !$forecast instanceof ForecastRouteRegistrar
                        || !$handoff instanceof InvoiceHandoffRouteRegistrar
                        || !$auth instanceof AuthRouteRegistrar
                        || !$users instanceof UserRouteRegistrar
                    ) {
                        throw new LogicException('Route registrar services are invalid.');
                    }

                    return [$stages, $deals, $forecast, $handoff, $auth, $users];
                },
            )
            ->set(
                self::EXCEPTION_HANDLERS,
                static function (ContainerInterface $container): array {
                    $dealNotFound = $container->get(DealNotFoundExceptionHandler::class);
                    $unknownStage = $container->get(UnknownStageExceptionHandler::class);
                    $alreadyHandedOff = $container->get(AlreadyHandedOffExceptionHandler::class);
                    $handoffPrecondition = $container->get(HandoffPreconditionExceptionHandler::class);
                    $invoiceUpstream = $container->get(InvoiceHandoffExceptionHandler::class);
                    $invalidCredentials = $container->get(InvalidCredentialsExceptionHandler::class);
                    $userNotFound = $container->get(UserNotFoundExceptionHandler::class);
                    $emailTaken = $container->get(EmailAlreadyTakenExceptionHandler::class);
                    $cannotModifySelf = $container->get(CannotModifySelfExceptionHandler::class);
                    $stageNotFound = $container->get(StageNotFoundExceptionHandler::class);
                    $stageHasDeals = $container->get(StageHasDealsExceptionHandler::class);
                    $stageDeletionForbidden = $container->get(StageDeletionForbiddenExceptionHandler::class);
                    $slugTaken = $container->get(SlugAlreadyTakenExceptionHandler::class);

                    if (!$dealNotFound instanceof DealNotFoundExceptionHandler
                        || !$unknownStage instanceof UnknownStageExceptionHandler
                        || !$alreadyHandedOff instanceof AlreadyHandedOffExceptionHandler
                        || !$handoffPrecondition instanceof HandoffPreconditionExceptionHandler
                        || !$invoiceUpstream instanceof InvoiceHandoffExceptionHandler
                        || !$invalidCredentials instanceof InvalidCredentialsExceptionHandler
                        || !$userNotFound instanceof UserNotFoundExceptionHandler
                        || !$emailTaken instanceof EmailAlreadyTakenExceptionHandler
                        || !$cannotModifySelf instanceof CannotModifySelfExceptionHandler
                        || !$stageNotFound instanceof StageNotFoundExceptionHandler
                        || !$stageHasDeals instanceof StageHasDealsExceptionHandler
                        || !$stageDeletionForbidden instanceof StageDeletionForbiddenExceptionHandler
                        || !$slugTaken instanceof SlugAlreadyTakenExceptionHandler
                    ) {
                        throw new LogicException('Exception handler services are invalid.');
                    }

                    return [
                        $dealNotFound,
                        $unknownStage,
                        $alreadyHandedOff,
                        $handoffPrecondition,
                        $invoiceUpstream,
                        $invalidCredentials,
                        $userNotFound,
                        $emailTaken,
                        $cannotModifySelf,
                        $stageNotFound,
                        $stageHasDeals,
                        $stageDeletionForbidden,
                        $slugTaken,
                    ];
                },
            );
    }
}
