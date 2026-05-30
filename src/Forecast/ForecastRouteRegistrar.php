<?php

declare(strict_types=1);

namespace NeneDeal\Forecast;

use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Registers the forecast read route.
 */
final readonly class ForecastRouteRegistrar
{
    public function __construct(
        private GetForecastHandler $handler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $forecast = $this->handler;

        $router->get('/api/v1/forecast', static fn (ServerRequestInterface $r): ResponseInterface => $forecast->handle($r));
    }
}
