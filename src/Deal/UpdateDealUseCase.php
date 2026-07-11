<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

use LogicException;
use Nene2\Audit\AuditEvent;
use Nene2\Audit\AuditRecorderInterface;
use NeneDeal\Audit\AuditAction;
use NeneDeal\Tenancy\CurrentOrganization;
use Symfony\Component\Uid\Ulid;

final readonly class UpdateDealUseCase
{
    public function __construct(
        private DealRepositoryInterface $deals,
        private AuditRecorderInterface $audit,
        private CurrentOrganization $organization,
    ) {
    }

    /** @throws DealNotFoundException */
    public function execute(string $id, UpdateDealInput $input, ?string $actorUserId = null): Deal
    {
        $deal = $this->deals->findById($id);

        if ($deal === null) {
            throw new DealNotFoundException($id);
        }

        $next = new Deal(
            id: $deal->id,
            accountLabel: $input->accountLabel ?? $deal->accountLabel,
            amountCents: $input->amountCents ?? $deal->amountCents,
            stageId: $deal->stageId,
            probabilityPercent: $input->probabilityPercent ?? $deal->probabilityPercent,
            expectedCloseDate: $input->hasExpectedCloseDate ? $input->expectedCloseDate : $deal->expectedCloseDate,
            ownerUserId: $input->hasOwnerUserId ? $input->ownerUserId : $deal->ownerUserId,
            note: $input->hasNote ? $input->note : $deal->note,
            invoiceClientId: $deal->invoiceClientId,
            invoiceQuoteId: $deal->invoiceQuoteId,
            handoffAt: $deal->handoffAt,
        );

        $changes = self::diff($deal, $next);

        $this->deals->update($next);

        if ($changes !== []) {
            $this->deals->recordActivity(new DealActivity(
                id: (string) new Ulid(),
                dealId: $deal->id,
                action: 'updated',
                actorUserId: $actorUserId,
                changes: $changes,
            ));

            $this->audit->record(new AuditEvent(
                action: AuditAction::DEAL_UPDATED,
                entityType: 'deal',
                entityId: $deal->id,
                actorId: $actorUserId,
                organizationId: $this->organization->id(),
                before: array_map(static fn (array $change): mixed => $change['from'], $changes),
                after: array_map(static fn (array $change): mixed => $change['to'], $changes),
            ));
        }

        $updated = $this->deals->findById($id);

        if ($updated === null) {
            throw new LogicException('Deal could not be loaded immediately after update.');
        }

        return $updated;
    }

    /**
     * Field-level diff for the audit trail. Stage and Invoice links are not
     * editable here, so they are never part of an `updated` entry.
     *
     * @return array<string, array{from: mixed, to: mixed}>
     */
    private static function diff(Deal $before, Deal $after): array
    {
        $changes = [];

        if ($before->accountLabel !== $after->accountLabel) {
            $changes['account_label'] = ['from' => $before->accountLabel, 'to' => $after->accountLabel];
        }
        if ($before->amountCents !== $after->amountCents) {
            $changes['amount_cents'] = ['from' => $before->amountCents, 'to' => $after->amountCents];
        }
        if ($before->probabilityPercent !== $after->probabilityPercent) {
            $changes['probability_percent'] = ['from' => $before->probabilityPercent, 'to' => $after->probabilityPercent];
        }
        if ($before->expectedCloseDate !== $after->expectedCloseDate) {
            $changes['expected_close_date'] = ['from' => $before->expectedCloseDate, 'to' => $after->expectedCloseDate];
        }
        if ($before->ownerUserId !== $after->ownerUserId) {
            $changes['owner_user_id'] = ['from' => $before->ownerUserId, 'to' => $after->ownerUserId];
        }
        if ($before->note !== $after->note) {
            $changes['note'] = ['from' => $before->note, 'to' => $after->note];
        }

        return $changes;
    }
}
