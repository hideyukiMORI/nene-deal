# ADR 0005: Mutation Audit Trail via NENE2 `Nene2\Audit`

## Status

Accepted

## Context

The 2026-07-11 structural consistency audit across the NeNe fleet (invoice /
clear / deal / vault) found Deal to be the only product without a mutation
audit-log foundation ([#89]). Invoice, Clear and Vault all consume
`Nene2\Audit` (`AuditRecorder` / `AuditEvent` / `PdoAuditEventRepository`);
Vault elevates "every mutation goes through AuditRecorder" to a hard rule.

Deal did have an audit-*looking* surface — the `GET /audit/export` CSV — but it
reads `deal_stage_history` only. Stage transitions were the sole recorded
mutation: deal create/edit/soft-delete, user management (RBAC changes),
stage-definition changes, settings changes, the Invoice handoff, and login
outcomes left no trace. For a product whose rows are deal amounts, and whose
handoff mutates a sibling product, that was a governance gap rated high.

[#89]: https://github.com/hideyukiMORI/nene-deal/issues/89

## Decision

1. **Adopt `Nene2\Audit` with the canonical table shape.** A new append-only
   `audit_events` table (canonical `AuditTableConfig` columns: `action`,
   `entity_type`, `entity_id`, `actor_id`, `organization_id`, `before_json`,
   `after_json`, `metadata_json`, `occurred_at`, auto-increment id). Actor and
   organization columns are **ULID strings (26)** — Deal's PKs are ULIDs (see
   ADR 0006 when it lands; org PK design).
2. **Hard rule: every mutation goes through `AuditRecorder`.** All mutation
   use cases record an event: deal create / update / stage change / soft-delete
   / restore, Invoice handoff, stage create / update / delete, user create /
   update / delete, settings update, and login success / failure. The product
   action vocabulary lives in `NeneDeal\Audit\AuditAction` and is registered in
   the terminology registry.
3. **Failed logins are recorded without actor or organization.** Recording the
   would-be org — like recording the password — could leak whether the account
   exists (clear shape). Only the attempted email and a coarse reason are kept.
4. **`deal_stage_history` stays, unchanged.** It is a *domain* history — the
   deal timeline the UI shows and the CSV export reads — not a governance
   trail. The two answer different questions: "how did this deal progress?"
   (stage history) vs "who changed what, when, across the whole tenant?"
   (audit events). Stage changes intentionally appear in both.
5. **The CSV export keeps reading `deal_stage_history` only** for now. Whether
   to add an `audit_events` series to the export is a separate product
   decision (deferred from #89).

## Consequences

- Every mutating request now writes one extra row on the same connection as
  the business mutation. Deal's use cases do not yet run inside explicit
  transactions (single-statement autocommit writes), so the audit write is
  sequential rather than transaction-atomic. When Deal adopts
  `DatabaseTransactionManagerInterface::transactional()`, call sites should
  move to `AuditRecorderFactoryInterface::forExecutor()` (vault shape) so the
  audit row commits atomically with the mutation.
- The recorder is wired without an organization holder: `NeneDeal`'s
  `RequestScopedHolder<string>` is not assignable to the framework's invariant
  `RequestScopedHolder<string|int>`, so every call site sets `organizationId`
  explicitly on the event (fleet-wide pattern, same as vault).
- Snapshots are sanitized at the call site: user events never include password
  material; update events carry only changed fields.
