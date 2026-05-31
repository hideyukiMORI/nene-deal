# Local MCP Server — NeNe Deal

Development-only stdio MCP server that exposes read-only tools so AI agents
can inspect Deal data through documented API boundaries.

## Starting the server

```bash
# Requires: Docker Compose stack running (app + MySQL)
docker compose run --rm \
  -e NENE2_LOCAL_API_BASE_URL=http://app \
  app \
  php vendor/hideyukimori/nene2/tools/local-mcp-server.php
```

The server reads `docs/mcp/tools.json` and forwards each tool call to the
NeNe Deal HTTP API at `NENE2_LOCAL_API_BASE_URL`.

## Prerequisites

```bash
docker compose up -d         # start app + MySQL
docker compose exec app composer install       # first run only
docker compose exec app composer migrations:migrate
```

## Available tools (read-only)

| Tool | Endpoint | Description |
|---|---|---|
| `listDeals` | `GET /api/v1/deals` | List deals with optional filters |
| `getDeal` | `GET /api/v1/deals/{dealId}` | Get a single deal |
| `listDealStageHistory` | `GET /api/v1/deals/{dealId}/history` | Stage-change audit trail |
| `listStages` | `GET /api/v1/stages` | Ordered pipeline stages |
| `getBoard` | `GET /api/v1/board` | Kanban board read model |
| `getForecast` | `GET /api/v1/forecast` | Weighted monthly forecast |
| `getCurrentUser` | `GET /api/v1/auth/me` | Current authenticated operator |

All tools require a valid bearer JWT when `NENE2_LOCAL_JWT_SECRET` is set
(i.e., in any non-trivial deployment). Pass the token via the standard
`Authorization: Bearer <token>` header — the MCP server forwards it.

## Catalog validation

```bash
composer mcp
```

Runs `tools/validate-mcp-tools.php` and verifies every tool is aligned with
`docs/openapi/openapi.yaml`. Included in `composer check`.

## Claude Desktop / MCP client config

```json
{
  "mcpServers": {
    "nene-deal": {
      "command": "docker",
      "args": [
        "compose", "run", "--rm",
        "-e", "NENE2_LOCAL_API_BASE_URL=http://app",
        "app",
        "php", "vendor/hideyukimori/nene2/tools/local-mcp-server.php"
      ]
    }
  }
}
```

## Security notes

- Read-only tools only — no write/mutation tools are exposed.
- The MCP server does not bypass application auth; it calls the HTTP API.
- Do not expose the local MCP server over a network interface in production.
- See `docs/mcp/tools.json` for the full safety classification of each tool.
