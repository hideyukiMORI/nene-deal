<?php

declare(strict_types=1);

namespace NeneDeal\Handoff;

use LogicException;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneDeal\Deal\DealRepositoryInterface;
use NeneDeal\Pipeline\PipelineStageRepositoryInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

/**
 * Wires the won-deal Invoice handoff: HTTP Invoice client (from env config),
 * use case, handler, domain exception handlers, and the route registrar.
 */
final readonly class HandoffServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(
                InvoiceClient::class,
                static function (ContainerInterface $c): InvoiceClient {
                    $psr17 = $c->get(Psr17Factory::class);

                    if (!$psr17 instanceof Psr17Factory) {
                        throw new LogicException('PSR-17 factory service is invalid.');
                    }

                    $psr18 = new Psr18Client(HttpClient::create(), $psr17, $psr17);

                    return new HttpInvoiceClient(
                        $psr18,
                        $psr17,
                        self::env('NENE_DEAL_INVOICE_BASE_URL'),
                        self::optionalEnv('NENE_DEAL_INVOICE_BEARER_TOKEN'),
                    );
                },
            )
            ->set(
                InvoiceHandoffUseCase::class,
                static function (ContainerInterface $c): InvoiceHandoffUseCase {
                    $deals = $c->get(DealRepositoryInterface::class);
                    $stages = $c->get(PipelineStageRepositoryInterface::class);
                    $invoice = $c->get(InvoiceClient::class);

                    if (!$deals instanceof DealRepositoryInterface) {
                        throw new LogicException('Deal repository service is invalid.');
                    }

                    if (!$stages instanceof PipelineStageRepositoryInterface) {
                        throw new LogicException('Pipeline stage repository service is invalid.');
                    }

                    if (!$invoice instanceof InvoiceClient) {
                        throw new LogicException('Invoice client service is invalid.');
                    }

                    return new InvoiceHandoffUseCase($deals, $stages, $invoice);
                },
            )
            ->set(
                InvoiceHandoffHandler::class,
                static function (ContainerInterface $c): InvoiceHandoffHandler {
                    $useCase = $c->get(InvoiceHandoffUseCase::class);
                    $json = $c->get(JsonResponseFactory::class);

                    if (!$useCase instanceof InvoiceHandoffUseCase) {
                        throw new LogicException('Invoice handoff use case service is invalid.');
                    }

                    if (!$json instanceof JsonResponseFactory) {
                        throw new LogicException('JSON response factory service is invalid.');
                    }

                    return new InvoiceHandoffHandler($useCase, $json);
                },
            )
            ->set(
                InvoiceHandoffRouteRegistrar::class,
                static function (ContainerInterface $c): InvoiceHandoffRouteRegistrar {
                    $handler = $c->get(InvoiceHandoffHandler::class);

                    if (!$handler instanceof InvoiceHandoffHandler) {
                        throw new LogicException('Invoice handoff handler service is invalid.');
                    }

                    return new InvoiceHandoffRouteRegistrar($handler);
                },
            )
            ->set(
                AlreadyHandedOffExceptionHandler::class,
                static fn (ContainerInterface $c): AlreadyHandedOffExceptionHandler => new AlreadyHandedOffExceptionHandler(self::problem($c)),
            )
            ->set(
                HandoffPreconditionExceptionHandler::class,
                static fn (ContainerInterface $c): HandoffPreconditionExceptionHandler => new HandoffPreconditionExceptionHandler(self::problem($c)),
            )
            ->set(
                InvoiceHandoffExceptionHandler::class,
                static fn (ContainerInterface $c): InvoiceHandoffExceptionHandler => new InvoiceHandoffExceptionHandler(self::problem($c)),
            );
    }

    private static function problem(ContainerInterface $c): ProblemDetailsResponseFactory
    {
        $p = $c->get(ProblemDetailsResponseFactory::class);

        if (!$p instanceof ProblemDetailsResponseFactory) {
            throw new LogicException('Problem details response factory service is invalid.');
        }

        return $p;
    }

    private static function env(string $key): string
    {
        return self::optionalEnv($key) ?? '';
    }

    private static function optionalEnv(string $key): ?string
    {
        $value = $_SERVER[$key] ?? $_ENV[$key] ?? getenv($key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
