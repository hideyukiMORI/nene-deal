<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

use Nene2\Routing\Router;
use NeneDeal\Auth\RequireRoleMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Registers pipeline routes: stage list/CRUD (admin), kanban board, forecast.
 */
final readonly class PipelineStageRouteRegistrar
{
    public function __construct(
        private ListStagesHandler $listHandler,
        private CreateStageHandler $createHandler,
        private UpdateStageHandler $updateHandler,
        private DeleteStageHandler $deleteHandler,
        private GetBoardHandler $boardHandler,
        private RequireRoleMiddleware $requireAdmin,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $list = $this->listHandler;
        $create = $this->createHandler;
        $update = $this->updateHandler;
        $delete = $this->deleteHandler;
        $board = $this->boardHandler;
        $admin = $this->requireAdmin;

        $router->get('/api/v1/stages', static fn (ServerRequestInterface $r): ResponseInterface => $list->handle($r));
        $router->post('/api/v1/stages', static fn (ServerRequestInterface $r): ResponseInterface => $admin->process($r, $create));
        $router->patch('/api/v1/stages/{stageId}', static fn (ServerRequestInterface $r): ResponseInterface => $admin->process($r, $update));
        $router->delete('/api/v1/stages/{stageId}', static fn (ServerRequestInterface $r): ResponseInterface => $admin->process($r, $delete));
        $router->get('/api/v1/board', static fn (ServerRequestInterface $r): ResponseInterface => $board->handle($r));
    }
}
