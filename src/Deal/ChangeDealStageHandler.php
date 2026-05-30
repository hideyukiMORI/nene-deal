<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * `POST /deals/{dealId}/stage-change` — moves a deal to another stage and
 * records a history entry. Won stages do not auto-trigger the Invoice handoff.
 */
final readonly class ChangeDealStageHandler implements RequestHandlerInterface
{
    public function __construct(
        private ChangeDealStageUseCase $useCase,
        private JsonResponseFactory $json,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_decode((string) $request->getBody(), true);

        if (!is_array($body)) {
            return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, 'Request body must be a JSON object.');
        }

        $toStageRef = $body['to_stage_id'] ?? null;

        if (!is_string($toStageRef) || $toStageRef === '') {
            return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, '"to_stage_id" is required.');
        }

        $deal = $this->useCase->execute(DealField::pathId($request), $toStageRef);

        return $this->json->create(DealResponse::toArray($deal));
    }
}
