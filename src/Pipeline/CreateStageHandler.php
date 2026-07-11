<?php

declare(strict_types=1);

namespace NeneDeal\Pipeline;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
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
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $errors = [];

        $label = $body['label'] ?? null;

        if (!is_string($label) || trim($label) === '') {
            $errors[] = new ValidationError('label', '"label" is required.', 'required');
        } elseif (mb_strlen($label) > 64) {
            $errors[] = new ValidationError('label', '"label" must be 64 characters or fewer.', 'invalid');
        }

        $sortOrder = $body['sort_order'] ?? null;

        if (!is_int($sortOrder) || $sortOrder < 0) {
            $errors[] = new ValidationError('sort_order', '"sort_order" must be a non-negative integer.', 'invalid');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        assert(is_string($label) && is_int($sortOrder));

        $stage = $this->useCase->execute(new CreateStageInput($label, $sortOrder), AuthContext::userId($request));

        return $this->json->create(PipelineStageResponse::toArray($stage), 201);
    }
}
