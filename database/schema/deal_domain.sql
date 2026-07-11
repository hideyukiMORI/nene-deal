-- SQLite schema for repository tests (mirrors database/migrations/*).
-- Loaded into an in-memory database by Pdo*RepositoryTest::setUp().

CREATE TABLE organizations (
    id TEXT PRIMARY KEY NOT NULL,
    slug TEXT NOT NULL,
    name TEXT NOT NULL,
    forecast_closing_day INTEGER DEFAULT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE UNIQUE INDEX uniq_organizations_slug ON organizations (slug);

CREATE TABLE users (
    id TEXT PRIMARY KEY NOT NULL,
    organization_id TEXT NOT NULL,
    email TEXT NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'operator',
    status TEXT NOT NULL DEFAULT 'active',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE UNIQUE INDEX uniq_users_email ON users (email);
CREATE INDEX idx_users_organization_id ON users (organization_id);

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
    updated_at TEXT NOT NULL,
    deleted_at TEXT DEFAULT NULL,
    deleted_by TEXT DEFAULT NULL
);
CREATE INDEX idx_deals_org_stage ON deals (organization_id, stage_id);
CREATE INDEX idx_deals_org_close_date ON deals (organization_id, expected_close_date);
CREATE INDEX idx_deals_org_deleted ON deals (organization_id, deleted_at);

CREATE TABLE audit_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    action TEXT NOT NULL,
    entity_type TEXT NOT NULL,
    entity_id TEXT DEFAULT NULL,
    actor_id TEXT DEFAULT NULL,
    organization_id TEXT DEFAULT NULL,
    before_json TEXT DEFAULT NULL,
    after_json TEXT DEFAULT NULL,
    metadata_json TEXT DEFAULT NULL,
    occurred_at TEXT NOT NULL
);
CREATE INDEX idx_audit_events_org ON audit_events (organization_id);
CREATE INDEX idx_audit_events_entity ON audit_events (entity_type, entity_id);
CREATE INDEX idx_audit_events_action ON audit_events (action);
CREATE INDEX idx_audit_events_actor ON audit_events (actor_id);
CREATE INDEX idx_audit_events_occurred_at ON audit_events (occurred_at);

CREATE TABLE login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    identifier_hash TEXT NOT NULL,
    attempt_count INTEGER NOT NULL DEFAULT 0,
    window_started_at TEXT NOT NULL,
    locked_until TEXT DEFAULT NULL
);
CREATE UNIQUE INDEX uq_login_attempts_identifier ON login_attempts (identifier_hash);

CREATE TABLE deal_stage_history (
    id TEXT PRIMARY KEY NOT NULL,
    deal_id TEXT NOT NULL,
    from_stage_id TEXT DEFAULT NULL,
    to_stage_id TEXT DEFAULT NULL,
    action TEXT NOT NULL DEFAULT 'stage_changed',
    changes TEXT DEFAULT NULL,
    actor_user_id TEXT DEFAULT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY (deal_id) REFERENCES deals (id) ON DELETE CASCADE
);
CREATE INDEX idx_deal_stage_history_deal ON deal_stage_history (deal_id, created_at);
