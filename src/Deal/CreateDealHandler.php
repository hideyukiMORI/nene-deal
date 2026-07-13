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
 * `POST /deals` — creates a deal in the caller's organization.
 */
final readonly class CreateDealHandler implements RequestHandlerInterface
{
    public function __construct(
        private CreateDealUseCase $useCase,
        private JsonResponseFactory $json,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $errors = [];

        $accountLabel = $body['account_label'] ?? null;

        if (!is_string($accountLabel) || trim($accountLabel) === '') {
            $errors[] = new ValidationError('account_label', '"account_label" is required.', 'required');
        } elseif (mb_strlen($accountLabel) > DealField::MAX_ACCOUNT_LABEL) {
            $errors[] = new ValidationError('account_label', '"account_label" must be ' . DealField::MAX_ACCOUNT_LABEL . ' characters or fewer.', 'invalid');
        }

        $amount = $body['amount_cents'] ?? null;

        if (!is_int($amount) || $amount < 0) {
            $errors[] = new ValidationError('amount_cents', '"amount_cents" must be a non-negative integer (JPY minor units).', 'invalid');
        }

        $stageRef = $body['stage_id'] ?? null;

        if (!is_string($stageRef) || $stageRef === '') {
            $errors[] = new ValidationError('stage_id', '"stage_id" is required.', 'required');
        }

        $probability = $body['probability_percent'] ?? 0;

        if (!is_int($probability) || $probability < 0 || $probability > 100) {
            $errors[] = new ValidationError('probability_percent', '"probability_percent" must be an integer between 0 and 100.', 'invalid');
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

        assert(is_string($accountLabel) && is_int($amount) && is_string($stageRef) && is_int($probability));

        $deal = $this->useCase->execute(new CreateDealInput(
            accountLabel: $accountLabel,
            amountCents: $amount,
            stageRef: $stageRef,
            probabilityPercent: $probability,
            expectedCloseDate: $expectedCloseDate,
            ownerUserId: $ownerUserId,
            note: $note,
        ), AuthContext::userId($request));

        return $this->json->create(DealResponse::toArray($deal), 201);
    }
}
