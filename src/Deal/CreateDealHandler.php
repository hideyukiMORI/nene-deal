<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use NeneDeal\Auth\AuthContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * `POST /deals` — creates a deal in the caller's organization.
 */
final readonly class CreateDealHandler implements RequestHandlerInterface
{
    public function __construct(
        private CreateDealUseCase $useCase,
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

        $accountLabel = $body['account_label'] ?? null;

        if (!is_string($accountLabel) || trim($accountLabel) === '') {
            return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, '"account_label" is required.');
        }

        $amount = $body['amount_cents'] ?? null;

        if (!is_int($amount) || $amount < 0) {
            return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, '"amount_cents" must be a non-negative integer (JPY minor units).');
        }

        $stageRef = $body['stage_id'] ?? null;

        if (!is_string($stageRef) || $stageRef === '') {
            return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, '"stage_id" is required.');
        }

        $probability = $body['probability_percent'] ?? 0;

        if (!is_int($probability) || $probability < 0 || $probability > 100) {
            return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, '"probability_percent" must be an integer between 0 and 100.');
        }

        $deal = $this->useCase->execute(new CreateDealInput(
            accountLabel: $accountLabel,
            amountCents: $amount,
            stageRef: $stageRef,
            probabilityPercent: $probability,
            expectedCloseDate: DealField::optionalString($body, 'expected_close_date'),
            ownerUserId: DealField::optionalString($body, 'owner_user_id'),
            note: DealField::optionalString($body, 'note'),
        ), AuthContext::userId($request));

        return $this->json->create(DealResponse::toArray($deal), 201);
    }
}
