<?php

declare(strict_types=1);

namespace NeneDeal\Forecast;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\ClockInterface;
use Nene2\Http\JsonResponseFactory;
use NeneDeal\Settings\OrganizationSettingsRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * `GET /forecast?month=YYYY-MM` — weighted forecast for a period (approximate).
 *
 * The aggregation window follows the organization's forecast closing day
 * (calendar month by default). `month` is optional: when omitted, the current
 * open period is returned (closing-day aware).
 */
final readonly class GetForecastHandler implements RequestHandlerInterface
{
    public function __construct(
        private ComputeForecastUseCase $useCase,
        private OrganizationSettingsRepositoryInterface $settings,
        private JsonResponseFactory $json,
        private ProblemDetailsResponseFactory $problemDetails,
        private ClockInterface $clock,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $closingDay = $this->settings->forecastClosingDay();
        $month = $request->getQueryParams()['month'] ?? null;

        if ($month === null || $month === '') {
            // No month → the period that contains today (closing-day aware).
            $month = ForecastPeriod::current($closingDay, $this->clock->now())->month;
        } elseif (!is_string($month) || preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            return $this->problemDetails->create($request, 'bad-request', 'Bad Request', 400, 'Query parameter "month" must be in YYYY-MM format.');
        } elseif ((int) substr($month, 5, 2) < 1 || (int) substr($month, 5, 2) > 12) {
            return $this->problemDetails->create($request, 'bad-request', 'Bad Request', 400, 'Query parameter "month" must use a month between 01 and 12.');
        }

        $summary = $this->useCase->execute($month, $closingDay);

        return $this->json->create(ForecastResponse::toArray($summary));
    }
}
