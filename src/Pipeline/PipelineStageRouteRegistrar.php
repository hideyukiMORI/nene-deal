<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Registers pipeline read routes.
 */
final readonly class PipelineStageRouteRegistrar
{
    public function __construct(
        private ListStagesHandler $listHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $list = $this->listHandler;

        $router->get('/stages', static fn (ServerRequestInterface $r): \Psr\Http\Message\ResponseInterface => $list->handle($r));
    }
}
