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
 * `PATCH /deals/{dealId}` — partial update (stage and Invoice link ids excluded).
 */
final readonly class UpdateDealHandler implements RequestHandlerInterface
{
    public function __construct(
        private UpdateDealUseCase $useCase,
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

        $accountLabel = null;
        if (array_key_exists('account_label', $body)) {
            $value = $body['account_label'];

            if (!is_string($value) || trim($value) === '') {
                return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, '"account_label" must be a non-empty string.');
            }

            $accountLabel = $value;
        }

        $amountCents = null;
        if (array_key_exists('amount_cents', $body)) {
            $value = $body['amount_cents'];

            if (!is_int($value) || $value < 0) {
                return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, '"amount_cents" must be a non-negative integer.');
            }

            $amountCents = $value;
        }

        $probability = null;
        if (array_key_exists('probability_percent', $body)) {
            $value = $body['probability_percent'];

            if (!is_int($value) || $value < 0 || $value > 100) {
                return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, '"probability_percent" must be an integer between 0 and 100.');
            }

            $probability = $value;
        }

        $deal = $this->useCase->execute(DealField::pathId($request), new UpdateDealInput(
            accountLabel: $accountLabel,
            amountCents: $amountCents,
            probabilityPercent: $probability,
            hasExpectedCloseDate: array_key_exists('expected_close_date', $body),
            expectedCloseDate: DealField::optionalString($body, 'expected_close_date'),
            hasOwnerUserId: array_key_exists('owner_user_id', $body),
            ownerUserId: DealField::optionalString($body, 'owner_user_id'),
            hasNote: array_key_exists('note', $body),
            note: DealField::optionalString($body, 'note'),
        ), AuthContext::userId($request));

        return $this->json->create(DealResponse::toArray($deal));
    }
}
