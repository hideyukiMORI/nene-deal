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
            } elseif (mb_strlen($value) > DealField::MAX_ACCOUNT_LABEL) {
                $errors[] = new ValidationError('account_label', '"account_label" must be ' . DealField::MAX_ACCOUNT_LABEL . ' characters or fewer.', 'invalid');
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

        $note = DealField::optionalString($body, 'note');

        if ($note !== null && mb_strlen($note) > DealField::MAX_NOTE) {
            $errors[] = new ValidationError('note', '"note" must be ' . DealField::MAX_NOTE . ' characters or fewer.', 'invalid');
        }

        $expectedCloseDate = DealField::optionalString($body, 'expected_close_date');

        if ($expectedCloseDate !== null && !DealField::isValidDate($expectedCloseDate)) {
            $errors[] = new ValidationError('expected_close_date', '"expected_close_date" must be a date in YYYY-MM-DD format.', 'invalid');
        }

        $ownerUserId = DealField::optionalString($body, 'owner_user_id');

        if ($ownerUserId !== null && !DealField::isValidUlid($ownerUserId)) {
            $errors[] = new ValidationError('owner_user_id', '"owner_user_id" must be a valid user id.', 'invalid');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $deal = $this->useCase->execute(DealField::pathId($request), new UpdateDealInput(
            accountLabel: $accountLabel,
            amountCents: $amountCents,
            probabilityPercent: $probability,
            hasExpectedCloseDate: array_key_exists('expected_close_date', $body),
            expectedCloseDate: $expectedCloseDate,
            hasOwnerUserId: array_key_exists('owner_user_id', $body),
            ownerUserId: $ownerUserId,
            hasNote: array_key_exists('note', $body),
            note: $note,
        ), AuthContext::userId($request));

        return $this->json->create(DealResponse::toArray($deal));
    }
}
