<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * `GET /deals/{dealId}/history` — full activity trail, newest first.
 */
final readonly class ListDealHistoryHandler implements RequestHandlerInterface
{
    public function __construct(
        private ListDealHistoryUseCase $useCase,
        private JsonResponseFactory $json,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $entries = $this->useCase->execute(DealField::pathId($request));

        $data = array_map(
            static fn (DealActivity $entry): array => DealActivityResponse::toArray($entry),
            $entries,
        );

        return $this->json->create(['data' => $data]);
    }
}
