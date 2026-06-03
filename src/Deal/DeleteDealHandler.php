<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

use Nene2\Http\JsonResponseFactory;
use NeneDeal\Auth\AuthContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * `DELETE /deals/{dealId}` — soft-deletes a deal (recoverable via restore).
 */
final readonly class DeleteDealHandler implements RequestHandlerInterface
{
    public function __construct(
        private DeleteDealUseCase $useCase,
        private JsonResponseFactory $json,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->useCase->execute(DealField::pathId($request), AuthContext::userId($request));

        return $this->json->createEmpty(204);
    }
}
