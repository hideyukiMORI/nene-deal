<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * `GET /board` — kanban read model (stages with their open deals and totals).
 */
final readonly class GetBoardHandler implements RequestHandlerInterface
{
    public function __construct(
        private BuildBoardUseCase $useCase,
        private JsonResponseFactory $json,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();

        $owner = isset($params['owner_user_id']) && is_string($params['owner_user_id']) && $params['owner_user_id'] !== ''
            ? $params['owner_user_id']
            : null;

        $includeTerminal = isset($params['include_terminal'])
            && is_string($params['include_terminal'])
            && in_array(strtolower($params['include_terminal']), ['1', 'true', 'yes', 'on'], true);

        $includeDeleted = isset($params['include_deleted'])
            && is_string($params['include_deleted'])
            && in_array(strtolower($params['include_deleted']), ['1', 'true', 'yes', 'on'], true);

        $board = $this->useCase->execute($owner, $includeTerminal, $includeDeleted);

        return $this->json->create(BoardResponse::toArray($board));
    }
}
