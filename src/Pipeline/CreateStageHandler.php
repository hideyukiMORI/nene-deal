<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneDeal\Auth\AuthContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** `POST /api/v1/stages` — creates a pipeline stage. Admin only. */
final readonly class CreateStageHandler implements RequestHandlerInterface
{
    public function __construct(
        private CreateStageUseCase $useCase,
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

        $label = $body['label'] ?? null;

        if (!is_string($label) || trim($label) === '') {
            return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, '"label" is required.');
        }

        if (mb_strlen($label) > 64) {
            return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, '"label" must be 64 characters or fewer.');
        }

        $sortOrder = $body['sort_order'] ?? null;

        if (!is_int($sortOrder) || $sortOrder < 0) {
            return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, '"sort_order" must be a non-negative integer.');
        }

        $stage = $this->useCase->execute(new CreateStageInput($label, $sortOrder), AuthContext::userId($request));

        return $this->json->create(PipelineStageResponse::toArray($stage), 201);
    }
}
