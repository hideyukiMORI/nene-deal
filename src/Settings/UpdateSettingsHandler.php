<?php

declare(strict_types=1);

namespace NeneDeal\Settings;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
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
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_decode((string) $request->getBody(), true);

        if (!is_array($body) || !array_key_exists('forecast_closing_day', $body)) {
            return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, '"forecast_closing_day" is required (null or an integer 1–28).');
        }

        $value = $body['forecast_closing_day'];

        if ($value !== null && (!is_int($value) || $value < 1 || $value > 28)) {
            return $this->problemDetails->create($request, 'validation-failed', 'Validation Failed', 422, '"forecast_closing_day" must be null (calendar month) or an integer between 1 and 28.');
        }

        $this->settings->updateForecastClosingDay($value);

        return $this->json->create(['forecast_closing_day' => $this->settings->forecastClosingDay()]);
    }
}
