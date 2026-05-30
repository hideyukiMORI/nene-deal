-- SQLite schema for repository tests (mirrors database/migrations/*).
-- Loaded into an in-memory database by Pdo*RepositoryTest::setUp().

CREATE TABLE organizations (
    id TEXT PRIMARY KEY NOT NULL,
    slug TEXT NOT NULL,
    name TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE UNIQUE INDEX uniq_organizations_slug ON organizations (slug);

CREATE TABLE pipeline_stages (
    id TEXT PRIMARY KEY NOT NULL,
    organization_id TEXT NOT NULL,
    slug TEXT NOT NULL,
    label TEXT NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    is_terminal INTEGER NOT NULL DEFAULT 0,
    is_won INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE UNIQUE INDEX uniq_pipeline_stages_org_slug ON pipeline_stages (organization_id, slug);
CREATE INDEX idx_pipeline_stages_org_order ON pipeline_stages (organization_id, sort_order);

CREATE TABLE deals (
    id TEXT PRIMARY KEY NOT NULL,
    organization_id TEXT NOT NULL,
    account_label TEXT NOT NULL,
    amount_cents INTEGER NOT NULL DEFAULT 0,
    stage_id TEXT NOT NULL,
    probability_percent INTEGER NOT NULL DEFAULT 0,
    expected_close_date TEXT DEFAULT NULL,
    owner_user_id TEXT DEFAULT NULL,
    note TEXT DEFAULT NULL,
    invoice_client_id INTEGER DEFAULT NULL,
    invoice_quote_id INTEGER DEFAULT NULL,
    handoff_at TEXT DEFAULT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE INDEX idx_deals_org_stage ON deals (organization_id, stage_id);
CREATE INDEX idx_deals_org_close_date ON deals (organization_id, expected_close_date);

CREATE TABLE deal_stage_history (
    id TEXT PRIMARY KEY NOT NULL,
    deal_id TEXT NOT NULL,
    from_stage_id TEXT DEFAULT NULL,
    to_stage_id TEXT NOT NULL,
    actor_user_id TEXT DEFAULT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY (deal_id) REFERENCES deals (id) ON DELETE CASCADE
);
CREATE INDEX idx_deal_stage_history_deal ON deal_stage_history (deal_id, created_at);
