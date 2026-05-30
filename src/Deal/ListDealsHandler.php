<?php

declare(strict_types=1);

namespace NeneDeal\Deal;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * `GET /deals` — lists deals for the caller's organization with filters and
 * opaque cursor pagination.
 */
final readonly class ListDealsHandler implements RequestHandlerInterface
{
    private const DEFAULT_LIMIT = 50;
    private const MAX_LIMIT = 200;

    public function __construct(
        private ListDealsUseCase $useCase,
        private JsonResponseFactory $json,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();

        $limit = self::DEFAULT_LIMIT;
        if (isset($params['limit']) && is_numeric($params['limit'])) {
            $limit = max(1, min(self::MAX_LIMIT, (int) $params['limit']));
        }

        $cursor = isset($params['cursor']) && is_string($params['cursor']) ? $params['cursor'] : null;
        $offset = Cursor::decodeOffset($cursor);

        $filter = new DealFilter(
            stageRef: self::stringParam($params, 'stage_id'),
            ownerUserId: self::stringParam($params, 'owner_user_id'),
            query: self::stringParam($params, 'q'),
            includeTerminal: self::boolParam($params, 'include_terminal'),
        );

        $page = $this->useCase->execute($filter, $limit, $offset);

        $data = array_map(
            static fn (Deal $deal): array => DealResponse::toArray($deal),
            $page->items,
        );

        return $this->json->create([
            'data' => $data,
            'next_cursor' => $page->hasMore ? Cursor::encode($offset + $limit) : null,
        ]);
    }

    /** @param array<string, mixed> $params */
    private static function stringParam(array $params, string $key): ?string
    {
        $value = $params[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /** @param array<string, mixed> $params */
    private static function boolParam(array $params, string $key): bool
    {
        $value = $params[$key] ?? null;

        return is_string($value) && in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }
}
