<?php

declare(strict_types=1);

namespace NeneDeal\Handoff;

use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Registers the won-deal Invoice handoff route.
 */
final readonly class InvoiceHandoffRouteRegistrar
{
    public function __construct(
        private InvoiceHandoffHandler $handler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $handoff = $this->handler;

        $router->post('/deals/{dealId}/invoice-handoff', static fn (ServerRequestInterface $r): ResponseInterface => $handoff->handle($r));
    }
}
