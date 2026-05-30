<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

/**
 * Serializes a {@see Deal} to its snake_case JSON representation
 * (see the Deal schema in docs/openapi/openapi.yaml).
 */
final class DealResponse
{
    /** @return array<string, mixed> */
    public static function toArray(Deal $deal): array
    {
        return [
            'id' => $deal->id,
            'organization_id' => $deal->organizationId,
            'account_label' => $deal->accountLabel,
            'amount_cents' => $deal->amountCents,
            'stage_id' => $deal->stageId,
            'stage_slug' => $deal->stageSlug,
            'probability_percent' => $deal->probabilityPercent,
            'expected_close_date' => $deal->expectedCloseDate,
            'owner_user_id' => $deal->ownerUserId,
            'note' => $deal->note,
            'invoice_client_id' => $deal->invoiceClientId,
            'invoice_quote_id' => $deal->invoiceQuoteId,
            'handoff_at' => $deal->handoffAt,
            'created_at' => $deal->createdAt,
            'updated_at' => $deal->updatedAt,
        ];
    }
}
