<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeneDeal\Auth\AuthContext;
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
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $toStageRef = $body['to_stage_id'] ?? null;

        if (!is_string($toStageRef) || $toStageRef === '') {
            throw new ValidationException([new ValidationError('to_stage_id', '"to_stage_id" is required.', 'required')]);
        }

        $deal = $this->useCase->execute(DealField::pathId($request), $toStageRef, AuthContext::userId($request));

        return $this->json->create(DealResponse::toArray($deal));
    }
}
