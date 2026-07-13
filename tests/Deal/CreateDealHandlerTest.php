<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Deal;

use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationException;
use NeneDeal\Deal\CreateDealHandler;
use NeneDeal\Deal\CreateDealUseCase;
use NeneDeal\Deal\DealField;
use NeneDeal\Pipeline\PipelineStage;
use NeneDeal\Tests\Support\InMemoryDealRepository;
use NeneDeal\Tests\Support\InMemoryPipelineStageRepository;
use NeneDeal\Tests\Support\RecordingAuditRecorder;
use NeneDeal\Tests\Support\StubCurrentOrganization;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

/**
 * Input-boundary regression for `POST /api/v1/deals` (assessment #121):
 * over-long / malformed string fields must fail closed as 422 rather than reach
 * the database and surface as an unhandled 500.
 */
final class CreateDealHandlerTest extends TestCase
{
    private CreateDealHandler $handler;
    private Psr17Factory $psr17;

    protected function setUp(): void
    {
        $stages = new InMemoryPipelineStageRepository([
            new PipelineStage('01STAGELEAD0000000000000AA', 'org', 'lead', 'Lead', 1, false, false),
        ]);
        $useCase = new CreateDealUseCase(
            new InMemoryDealRepository(),
            $stages,
            new RecordingAuditRecorder(),
            new StubCurrentOrganization('01ORG00000000000000000000A'),
        );
        $this->psr17 = new Psr17Factory();
        $this->handler = new CreateDealHandler($useCase, new JsonResponseFactory($this->psr17, $this->psr17));
    }

    public function test_valid_payload_creates_deal(): void
    {
        $response = $this->post([
            'account_label' => 'Acme',
            'amount_cents' => 1000,
            'stage_id' => 'lead',
            'expected_close_date' => '2026-12-31',
            'owner_user_id' => '01ARZ3NDEKTSV4RRFFQ69G5FAV',
            'note' => 'ok',
        ]);

        self::assertSame(201, $response->getStatusCode());
    }

    public function test_rejects_account_label_over_max_length(): void
    {
        $this->expectException(ValidationException::class);

        $this->post([
            'account_label' => str_repeat('A', DealField::MAX_ACCOUNT_LABEL + 1),
            'amount_cents' => 1000,
            'stage_id' => 'lead',
        ]);
    }

    public function test_rejects_note_over_max_length(): void
    {
        $this->expectException(ValidationException::class);

        $this->post([
            'account_label' => 'Acme',
            'amount_cents' => 1000,
            'stage_id' => 'lead',
            'note' => str_repeat('B', DealField::MAX_NOTE + 1),
        ]);
    }

    #[\PHPUnit\Framework\Attributes\TestWith(['not-a-date'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['2026-13-45'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['2026/12/31'])]
    public function test_rejects_invalid_expected_close_date(string $date): void
    {
        $this->expectException(ValidationException::class);

        $this->post([
            'account_label' => 'Acme',
            'amount_cents' => 1000,
            'stage_id' => 'lead',
            'expected_close_date' => $date,
        ]);
    }

    public function test_rejects_non_ulid_owner_user_id(): void
    {
        $this->expectException(ValidationException::class);

        $this->post([
            'account_label' => 'Acme',
            'amount_cents' => 1000,
            'stage_id' => 'lead',
            'owner_user_id' => str_repeat('x', 300),
        ]);
    }

    /** @param array<string, mixed> $body */
    private function post(array $body): \Psr\Http\Message\ResponseInterface
    {
        $request = $this->psr17->createServerRequest('POST', '/api/v1/deals')
            ->withBody($this->psr17->createStream((string) json_encode($body)));

        return $this->handler->handle($request);
    }
}
