<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** `PATCH /api/v1/stages/{stageId}` — updates label and/or sort_order. Admin only. */
final readonly class UpdateStageHandler implements RequestHandlerInterface
{
    public function __construct(
        private UpdateStageUseCase $useCase,
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

        $label = null;

        if (array_key_exists('label', $body)) {
            $label = $body['label'];

            if (!is_string($label) || trim($label) === '') {
                return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, '"label" must be a non-empty string.');
            }

            if (mb_strlen($label) > 64) {
                return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, '"label" must be 64 characters or fewer.');
            }
        }

        $sortOrder = null;

        if (array_key_exists('sort_order', $body)) {
            $sortOrder = $body['sort_order'];

            if (!is_int($sortOrder) || $sortOrder < 0) {
                return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, '"sort_order" must be a non-negative integer.');
            }
        }

        if ($label === null && $sortOrder === null) {
            return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, 'At least one of "label" or "sort_order" is required.');
        }

        $stageId = Router::param($request, 'stageId') ?? '';
        $stage = $this->useCase->execute($stageId, new UpdateStageInput($label, $sortOrder));

        return $this->json->create(PipelineStageResponse::toArray($stage));
    }
}
