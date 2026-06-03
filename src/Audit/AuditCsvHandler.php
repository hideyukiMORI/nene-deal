<?php

declare(strict_types=1);

namespace NeneDeal\Audit;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * `GET /api/v1/audit/export?from=YYYY-MM-DD&to=YYYY-MM-DD` — downloads the
 * organization's audit trail for the inclusive date range as CSV. Admin only
 * (gated by the route registrar).
 */
final readonly class AuditCsvHandler implements RequestHandlerInterface
{
    public function __construct(
        private ExportAuditUseCase $useCase,
        private Psr17Factory $psr17,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $from = is_string($params['from'] ?? null) ? $params['from'] : '';
        $to = is_string($params['to'] ?? null) ? $params['to'] : '';

        if (!self::isDate($from) || !self::isDate($to)) {
            return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, '"from" and "to" must be dates in YYYY-MM-DD format.');
        }

        if ($from > $to) {
            return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, '"from" must not be after "to".');
        }

        $rows = $this->useCase->execute($from . ' 00:00:00', $to . ' 23:59:59');

        $csv = self::toCsv($rows);
        $filename = "audit-{$from}_{$to}.csv";

        return $this->psr17->createResponse(200)
            ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withBody($this->psr17->createStream($csv));
    }

    private static function isDate(string $value): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
    }

    /** @param list<AuditExportRow> $rows */
    private static function toCsv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return '';
        }

        // UTF-8 BOM so Excel (incl. Japanese locales) reads the file correctly.
        fwrite($handle, "\xEF\xBB\xBF");

        // Column schema per the design spec: one row per changed field.
        fputcsv($handle, ['timestamp', 'actor', 'action', 'deal_id', 'field', 'before', 'after'], ',', '"', '');

        foreach ($rows as $row) {
            foreach (self::fieldRows($row) as [$field, $before, $after]) {
                fputcsv($handle, [
                    $row->createdAt,
                    $row->actorLabel ?? '',
                    $row->action,
                    $row->dealId,
                    $field,
                    $before,
                    $after,
                ], ',', '"', '');
            }
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv === false ? '' : $csv;
    }

    /**
     * Flattens one activity entry into one CSV line per changed field.
     * Stage moves report a single `stage` field; events without field-level
     * changes (created/deleted/restored) report a single empty-field row.
     *
     * @return list<array{0: string, 1: string, 2: string}>
     */
    private static function fieldRows(AuditExportRow $row): array
    {
        if ($row->action === 'stage_changed') {
            return [['stage', $row->fromStageLabel ?? '', $row->toStageLabel ?? '']];
        }

        if ($row->changes !== null && $row->changes !== []) {
            $out = [];
            foreach ($row->changes as $field => $change) {
                $out[] = [(string) $field, self::scalar($change['from'] ?? null), self::scalar($change['to'] ?? null)];
            }

            return $out;
        }

        return [['', '', '']];
    }

    private static function scalar(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_scalar($value)) {
            return (string) $value;
        }

        return (string) json_encode($value);
    }
}
