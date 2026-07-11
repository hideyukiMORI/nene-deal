<?php

declare(strict_types=1);

namespace NeneDeal\Settings;

use Nene2\Audit\AuditEvent;
use Nene2\Audit\AuditRecorderInterface;
use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeneDeal\Audit\AuditAction;
use NeneDeal\Auth\AuthContext;
use NeneDeal\Tenancy\CurrentOrganization;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * `PATCH /api/v1/settings` — updates organization settings. Admin only (gated
 * by the route registrar). `forecast_closing_day` is null (calendar month) or
 * an integer 1–28.
 */
final readonly class UpdateSettingsHandler implements RequestHandlerInterface
{
    public function __construct(
        private OrganizationSettingsRepositoryInterface $settings,
        private JsonResponseFactory $json,
        private AuditRecorderInterface $audit,
        private CurrentOrganization $organization,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        if (!array_key_exists('forecast_closing_day', $body)) {
            throw new ValidationException([new ValidationError('forecast_closing_day', '"forecast_closing_day" is required (null or an integer 1–28).', 'required')]);
        }

        $value = $body['forecast_closing_day'];

        if ($value !== null && (!is_int($value) || $value < 1 || $value > 28)) {
            throw new ValidationException([new ValidationError('forecast_closing_day', '"forecast_closing_day" must be null (calendar month) or an integer between 1 and 28.', 'invalid')]);
        }

        $previous = $this->settings->forecastClosingDay();

        $this->settings->updateForecastClosingDay($value);

        if ($value !== $previous) {
            $this->audit->record(new AuditEvent(
                action: AuditAction::SETTINGS_UPDATED,
                entityType: 'settings',
                entityId: $this->organization->id(),
                actorId: AuthContext::userId($request),
                organizationId: $this->organization->id(),
                before: ['forecast_closing_day' => $previous],
                after: ['forecast_closing_day' => $value],
            ));
        }

        return $this->json->create(['forecast_closing_day' => $this->settings->forecastClosingDay()]);
    }
}
