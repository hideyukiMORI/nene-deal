<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use NeneDeal\Auth\AuthContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** `DELETE /api/v1/stages/{stageId}` — deletes a stage. Admin only. */
final readonly class DeleteStageHandler implements RequestHandlerInterface
{
    public function __construct(
        private DeleteStageUseCase $useCase,
        private JsonResponseFactory $json,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $stageId = Router::param($request, 'stageId') ?? '';
        $this->useCase->execute($stageId, AuthContext::userId($request));

        return $this->json->createEmpty(204);
    }
}
