<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * `GET /deals/{dealId}` — returns a single deal.
 */
final readonly class GetDealHandler implements RequestHandlerInterface
{
    public function __construct(
        private GetDealUseCase $useCase,
        private JsonResponseFactory $json,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $deal = $this->useCase->execute(DealField::pathId($request));

        return $this->json->create(DealResponse::toArray($deal));
    }
}
