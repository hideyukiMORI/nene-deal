<?php

declare(strict_types=1);

namespace NeneDeal\Settings;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * `GET /api/v1/settings` — organization settings (readable by any operator;
 * the forecast period uses the closing day).
 */
final readonly class GetSettingsHandler implements RequestHandlerInterface
{
    public function __construct(
        private OrganizationSettingsRepositoryInterface $settings,
        private JsonResponseFactory $json,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->json->create(['forecast_closing_day' => $this->settings->forecastClosingDay()]);
    }
}
