<?php

declare(strict_types=1);

namespace NeneDeal\Forecast;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * `GET /forecast?month=YYYY-MM` — monthly weighted forecast (approximate).
 */
final readonly class GetForecastHandler implements RequestHandlerInterface
{
    public function __construct(
        private ComputeForecastUseCase $useCase,
        private JsonResponseFactory $json,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $month = $params['month'] ?? null;

        if (!is_string($month) || preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            return $this->problemDetails->create($request, 'bad-request', 'Bad Request', 400, 'Query parameter "month" is required in YYYY-MM format.');
        }

        $monthNumber = (int) substr($month, 5, 2);

        if ($monthNumber < 1 || $monthNumber > 12) {
            return $this->problemDetails->create($request, 'bad-request', 'Bad Request', 400, 'Query parameter "month" must use a month between 01 and 12.');
        }

        $summary = $this->useCase->execute($month);

        return $this->json->create(ForecastResponse::toArray($summary));
    }
}
