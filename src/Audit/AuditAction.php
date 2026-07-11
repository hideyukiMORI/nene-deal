<?php

declare(strict_types=1);

namespace NeneDeal\Audit;

/**
 * Product-owned audit action vocabulary (#89, ADR 0005). NENE2 stores the
 * strings verbatim; the canonical spellings are registered in
 * docs/explanation/terminology.md §Audit actions.
 */
final class AuditAction
{
    public const DEAL_CREATED = 'deal.created';
    public const DEAL_UPDATED = 'deal.updated';
    public const DEAL_STAGE_CHANGED = 'deal.stage_changed';
    public const DEAL_DELETED = 'deal.deleted';
    public const DEAL_RESTORED = 'deal.restored';
    public const DEAL_INVOICE_HANDOFF = 'deal.invoice_handoff';

    public const STAGE_CREATED = 'stage.created';
    public const STAGE_UPDATED = 'stage.updated';
    public const STAGE_DELETED = 'stage.deleted';

    public const USER_CREATED = 'user.created';
    public const USER_UPDATED = 'user.updated';
    public const USER_DELETED = 'user.deleted';

    public const SETTINGS_UPDATED = 'settings.updated';

    public const LOGIN_SUCCEEDED = 'login_succeeded';
    public const LOGIN_FAILED = 'login_failed';

    private function __construct()
    {
    }
}
