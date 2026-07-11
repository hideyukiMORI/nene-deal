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
 * `PATCH /deals/{dealId}` — partial update (stage and Invoice link ids excluded).
 */
final readonly class UpdateDealHandler implements RequestHandlerInterface
{
    public function __construct(
        private UpdateDealUseCase $useCase,
        private JsonResponseFactory $json,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $errors = [];

        $accountLabel = null;
        if (array_key_exists('account_label', $body)) {
            $value = $body['account_label'];

            if (!is_string($value) || trim($value) === '') {
                $errors[] = new ValidationError('account_label', '"account_label" must be a non-empty string.', 'invalid');
            } else {
                $accountLabel = $value;
            }
        }

        $amountCents = null;
        if (array_key_exists('amount_cents', $body)) {
            $value = $body['amount_cents'];

            if (!is_int($value) || $value < 0) {
                $errors[] = new ValidationError('amount_cents', '"amount_cents" must be a non-negative integer.', 'invalid');
            } else {
                $amountCents = $value;
            }
        }

        $probability = null;
        if (array_key_exists('probability_percent', $body)) {
            $value = $body['probability_percent'];

            if (!is_int($value) || $value < 0 || $value > 100) {
                $errors[] = new ValidationError('probability_percent', '"probability_percent" must be an integer between 0 and 100.', 'invalid');
            } else {
                $probability = $value;
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
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
