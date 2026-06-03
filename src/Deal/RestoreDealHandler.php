<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

use Nene2\Http\JsonResponseFactory;
use NeneDeal\Auth\AuthContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * `POST /deals/{dealId}/restore` — brings a soft-deleted deal back to the board.
 */
final readonly class RestoreDealHandler implements RequestHandlerInterface
{
    public function __construct(
        private RestoreDealUseCase $useCase,
        private JsonResponseFactory $json,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $deal = $this->useCase->execute(DealField::pathId($request), AuthContext::userId($request));

        return $this->json->create(DealResponse::toArray($deal));
    }
}
