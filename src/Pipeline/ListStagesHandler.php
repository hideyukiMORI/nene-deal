<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * `GET /stages` — lists the organization's pipeline stages in column order.
 */
final readonly class ListStagesHandler implements RequestHandlerInterface
{
    public function __construct(
        private ListStagesUseCase $useCase,
        private JsonResponseFactory $json,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = array_map(
            static fn (PipelineStage $stage): array => PipelineStageResponse::toArray($stage),
            $this->useCase->execute(),
        );

        return $this->json->create(['data' => $data]);
    }
}
