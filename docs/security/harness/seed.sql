-- Security-assessment seed (authorized self/maintainer-run diagnostic).
-- Loaded AFTER `phinx migrate` has created the schema and seeded the sole
-- "default" org. Adds two tenants (org-a / org-b) with their own stages, users
-- and deals so tenant isolation, RBAC, state transitions and info-leak can be
-- exercised against a live MySQL (strict mode, mirrors production).
--
-- Every id is a fixed, well-known ULID so the test script can reference rows
-- without a round-trip. Passwords are the throwaway `Passw0rd!23` (bcrypt cost
-- 12). NOT production values.
USE nene_deal;

-- Two tenants beside the migration's "default" org (3 orgs total, so the
-- single-tenant sole-org fallback is disabled — realistic multi-tenant).
INSERT INTO organizations (id, slug, name, created_at, updated_at) VALUES
 ('01KXDXRM70N9BESBY7DWB773K2', 'org-a', 'Org A KK',   '2026-07-13 00:00:00', '2026-07-13 00:00:00'),
 ('01KXDXRM70N9BESBY7DWB773K3', 'org-b', 'Org B KK',   '2026-07-13 00:00:00', '2026-07-13 00:00:00');

-- One non-terminal + one won stage per tenant.
INSERT INTO pipeline_stages (id, organization_id, slug, label, sort_order, is_terminal, is_won, created_at, updated_at) VALUES
 ('01KXDXRM70N9BESBY7DWB773K4', '01KXDXRM70N9BESBY7DWB773K2', 'lead', 'Lead', 1, 0, 0, '2026-07-13 00:00:00', '2026-07-13 00:00:00'),
 ('01KXDXRM70N9BESBY7DWB773K5', '01KXDXRM70N9BESBY7DWB773K2', 'won',  'Won',  5, 1, 1, '2026-07-13 00:00:00', '2026-07-13 00:00:00'),
 ('01KXDXRM70N9BESBY7DWB773K6', '01KXDXRM70N9BESBY7DWB773K3', 'lead', 'Lead', 1, 0, 0, '2026-07-13 00:00:00', '2026-07-13 00:00:00'),
 ('01KXDXRM71B5DV8Q5SE7T5C70X', '01KXDXRM70N9BESBY7DWB773K3', 'won',  'Won',  5, 1, 1, '2026-07-13 00:00:00', '2026-07-13 00:00:00');

-- admin + operator per tenant. Hash is bcrypt("Passw0rd!23", cost 12).
INSERT INTO users (id, organization_id, email, password_hash, role, status, created_at, updated_at) VALUES
 ('01KXDXRM71B5DV8Q5SE7T5C70Y', '01KXDXRM70N9BESBY7DWB773K2', 'admin-a@a.test', '$2y$12$eL18diRgaAzAmd1a6RzVwu7DPVokLF7AJ4M23zgqDw93DxgNu.bgO', 'admin',    'active', '2026-07-13 00:00:00', '2026-07-13 00:00:00'),
 ('01KXDXRM71B5DV8Q5SE7T5C70Z', '01KXDXRM70N9BESBY7DWB773K2', 'op-a@a.test',    '$2y$12$eL18diRgaAzAmd1a6RzVwu7DPVokLF7AJ4M23zgqDw93DxgNu.bgO', 'operator', 'active', '2026-07-13 00:00:00', '2026-07-13 00:00:00'),
 ('01KXDXRM71B5DV8Q5SE7T5C710', '01KXDXRM70N9BESBY7DWB773K3', 'admin-b@b.test', '$2y$12$eL18diRgaAzAmd1a6RzVwu7DPVokLF7AJ4M23zgqDw93DxgNu.bgO', 'admin',    'active', '2026-07-13 00:00:00', '2026-07-13 00:00:00'),
 ('01KXDXRM71B5DV8Q5SE7T5C711', '01KXDXRM70N9BESBY7DWB773K3', 'op-b@b.test',    '$2y$12$eL18diRgaAzAmd1a6RzVwu7DPVokLF7AJ4M23zgqDw93DxgNu.bgO', 'operator', 'active', '2026-07-13 00:00:00', '2026-07-13 00:00:00');

-- Deals. DEALB_SECRET carries a distinctive label so cross-tenant reads are
-- obvious if they leak.
INSERT INTO deals (id, organization_id, account_label, amount_cents, stage_id, probability_percent, expected_close_date, owner_user_id, note, created_at, updated_at) VALUES
 ('01KXDXRM71B5DV8Q5SE7T5C712', '01KXDXRM70N9BESBY7DWB773K2', 'Acme Corp (Org A)',        5000,    '01KXDXRM70N9BESBY7DWB773K4', 40, '2026-08-31', '01KXDXRM71B5DV8Q5SE7T5C70Z', NULL, '2026-07-13 00:00:00', '2026-07-13 00:00:00'),
 ('01KXDXRM71B5DV8Q5SE7T5C713', '01KXDXRM70N9BESBY7DWB773K2', 'Won Deal (Org A)',         10000,   '01KXDXRM70N9BESBY7DWB773K5', 100,'2026-08-31', '01KXDXRM71B5DV8Q5SE7T5C70Z', NULL, '2026-07-13 00:00:00', '2026-07-13 00:00:00'),
 ('01KXDXRM71B5DV8Q5SE7T5C714', '01KXDXRM70N9BESBY7DWB773K3', 'ORG-B-CONFIDENTIAL 機密案件', 7777,    '01KXDXRM70N9BESBY7DWB773K6', 30, '2026-09-30', '01KXDXRM71B5DV8Q5SE7T5C711', 'secret note', '2026-07-13 00:00:00', '2026-07-13 00:00:00');
