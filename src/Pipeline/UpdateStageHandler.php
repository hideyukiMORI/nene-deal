<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeneDeal\Auth\AuthContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** `PATCH /api/v1/stages/{stageId}` — updates label and/or sort_order. Admin only. */
final readonly class UpdateStageHandler implements RequestHandlerInterface
{
    public function __construct(
        private UpdateStageUseCase $useCase,
        private JsonResponseFactory $json,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $errors = [];

        $label = null;

        if (array_key_exists('label', $body)) {
            $value = $body['label'];

            if (!is_string($value) || trim($value) === '') {
                $errors[] = new ValidationError('label', '"label" must be a non-empty string.', 'invalid');
            } elseif (mb_strlen($value) > 64) {
                $errors[] = new ValidationError('label', '"label" must be 64 characters or fewer.', 'invalid');
            } else {
                $label = $value;
            }
        }

        $sortOrder = null;

        if (array_key_exists('sort_order', $body)) {
            $value = $body['sort_order'];

            if (!is_int($value) || $value < 0) {
                $errors[] = new ValidationError('sort_order', '"sort_order" must be a non-negative integer.', 'invalid');
            } else {
                $sortOrder = $value;
            }
        }

        if ($errors === [] && $label === null && $sortOrder === null) {
            $errors[] = new ValidationError('body', 'At least one of "label" or "sort_order" is required.', 'required');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $stageId = Router::param($request, 'stageId') ?? '';
        $stage = $this->useCase->execute($stageId, new UpdateStageInput($label, $sortOrder), AuthContext::userId($request));

        return $this->json->create(PipelineStageResponse::toArray($stage));
    }
}
