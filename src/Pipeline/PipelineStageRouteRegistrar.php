<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Registers pipeline read routes: stage list and the kanban board.
 */
final readonly class PipelineStageRouteRegistrar
{
    public function __construct(
        private ListStagesHandler $listHandler,
        private GetBoardHandler $boardHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $list = $this->listHandler;
        $board = $this->boardHandler;

        $router->get('/api/v1/stages', static fn (ServerRequestInterface $r): ResponseInterface => $list->handle($r));
        $router->get('/api/v1/board', static fn (ServerRequestInterface $r): ResponseInterface => $board->handle($r));
    }
}
