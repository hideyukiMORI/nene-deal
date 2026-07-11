<?php

declare(strict_types=1);

namespace NeneDeal\Audit;

use Nene2\Export\CsvWriter;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
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
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $from = is_string($params['from'] ?? null) ? $params['from'] : '';
        $to = is_string($params['to'] ?? null) ? $params['to'] : '';

        $errors = [];

        if (!self::isDate($from)) {
            $errors[] = new ValidationError('from', '"from" must be a date in YYYY-MM-DD format.', 'invalid');
        }

        if (!self::isDate($to)) {
            $errors[] = new ValidationError('to', '"to" must be a date in YYYY-MM-DD format.', 'invalid');
        }

        if ($errors === [] && $from > $to) {
            $errors[] = new ValidationError('from', '"from" must not be after "to".', 'invalid');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
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

        // `Nene2\Export\CsvWriter` (NENE2 v1.8.0, ADR 0015) neutralises
        // formula injection in string cells and emits a UTF-8 BOM by default, so
        // attacker-controlled audit `before`/`after` values (arbitrary text a user
        // may have entered into a deal field) can no longer execute as a formula
        // when the file is opened in Excel / LibreOffice / Google Sheets.
        // Column schema per the design spec: one row per changed field.
        $writer = new CsvWriter(
            $handle,
            headers: ['timestamp', 'actor', 'action', 'deal_id', 'field', 'before', 'after'],
        );

        // Pass a generator so the BOM and header row are emitted even for an empty
        // export (`writeAll` writes the prologue regardless), keeping an empty range
        // a valid header-only CSV rather than a zero-byte file.
        $writer->writeAll(self::toRows($rows));

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv === false ? '' : $csv;
    }

    /**
     * Flattens each audit entry into one CSV row per changed field.
     *
     * @param list<AuditExportRow> $rows
     *
     * @return iterable<list<string>>
     */
    private static function toRows(array $rows): iterable
    {
        foreach ($rows as $row) {
            foreach (self::fieldRows($row) as [$field, $before, $after]) {
                yield [
                    $row->createdAt,
                    $row->actorLabel ?? '',
                    $row->action,
                    $row->dealId,
                    $field,
                    $before,
                    $after,
                ];
            }
        }
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
