<?php

declare(strict_types=1);

/**
 * Serves the NeNe Deal OpenAPI document over HTTP without authentication.
 *
 * Usage:
 *   GET /openapi.php — the public API spec (docs/openapi/openapi.yaml)
 */

$specPath = dirname(__DIR__) . '/docs/openapi/openapi.yaml';

if (!is_file($specPath)) {
    http_response_code(404);
    header('Content-Type: application/problem+json; charset=utf-8');
    echo json_encode([
        'type'   => 'https://nene-deal.dev/problems/not-found',
        'title'  => 'Not Found',
        'status' => 404,
        'detail' => 'OpenAPI spec file not found on this server.',
    ], JSON_THROW_ON_ERROR);
    exit;
}

header('Content-Type: application/yaml; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=3600');
readfile($specPath);
