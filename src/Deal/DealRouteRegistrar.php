<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Registers deal routes (CRUD + stage move + history).
 */
final readonly class DealRouteRegistrar
{
    public function __construct(
        private ListDealsHandler $listHandler,
        private CreateDealHandler $createHandler,
        private GetDealHandler $getHandler,
        private UpdateDealHandler $updateHandler,
        private DeleteDealHandler $deleteHandler,
        private ChangeDealStageHandler $stageChangeHandler,
        private ListDealHistoryHandler $historyHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $list = $this->listHandler;
        $create = $this->createHandler;
        $get = $this->getHandler;
        $update = $this->updateHandler;
        $delete = $this->deleteHandler;
        $stageChange = $this->stageChangeHandler;
        $history = $this->historyHandler;

        $router->get('/deals', static fn (ServerRequestInterface $r): ResponseInterface => $list->handle($r));
        $router->post('/deals', static fn (ServerRequestInterface $r): ResponseInterface => $create->handle($r));
        $router->get('/deals/{dealId}', static fn (ServerRequestInterface $r): ResponseInterface => $get->handle($r));
        $router->patch('/deals/{dealId}', static fn (ServerRequestInterface $r): ResponseInterface => $update->handle($r));
        $router->delete('/deals/{dealId}', static fn (ServerRequestInterface $r): ResponseInterface => $delete->handle($r));
        $router->post('/deals/{dealId}/stage-change', static fn (ServerRequestInterface $r): ResponseInterface => $stageChange->handle($r));
        $router->get('/deals/{dealId}/history', static fn (ServerRequestInterface $r): ResponseInterface => $history->handle($r));
    }
}
