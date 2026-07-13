<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Deal;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationException;
use NeneDeal\Deal\Deal;
use NeneDeal\Deal\DealField;
use NeneDeal\Deal\UpdateDealHandler;
use NeneDeal\Deal\UpdateDealUseCase;
use NeneDeal\Tests\Support\InMemoryDealRepository;
use NeneDeal\Tests\Support\RecordingAuditRecorder;
use NeneDeal\Tests\Support\StubCurrentOrganization;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

/**
 * Input-boundary regression for `PATCH /api/v1/deals/{dealId}` (assessment
 * #121): over-long / malformed string fields must fail closed as 422 rather than
 * reach the database as an unhandled 500.
 */
final class UpdateDealHandlerTest extends TestCase
{
    private const DEAL_ID = '01DEAL0000000000000000000A';

    private UpdateDealHandler $handler;
    private Psr17Factory $psr17;

    protected function setUp(): void
    {
        $deals = new InMemoryDealRepository();
        $deals->save(new Deal(
            id: self::DEAL_ID,
            accountLabel: 'Acme',
            amountCents: 1000,
            stageId: '01STAGELEAD0000000000000AA',
            probabilityPercent: 10,
        ));
        $useCase = new UpdateDealUseCase(
            $deals,
            new RecordingAuditRecorder(),
            new StubCurrentOrganization('01ORG00000000000000000000A'),
        );
        $this->psr17 = new Psr17Factory();
        $this->handler = new UpdateDealHandler($useCase, new JsonResponseFactory($this->psr17, $this->psr17));
    }

    public function test_valid_partial_update_succeeds(): void
    {
        $response = $this->patch(['account_label' => 'Renamed', 'expected_close_date' => '2027-01-15']);

        self::assertSame(200, $response->getStatusCode());
    }

    public function test_rejects_account_label_over_max_length(): void
    {
        $this->expectException(ValidationException::class);

        $this->patch(['account_label' => str_repeat('A', DealField::MAX_ACCOUNT_LABEL + 1)]);
    }

    public function test_rejects_note_over_max_length(): void
    {
        $this->expectException(ValidationException::class);

        $this->patch(['note' => str_repeat('B', DealField::MAX_NOTE + 1)]);
    }

    public function test_rejects_invalid_expected_close_date(): void
    {
        $this->expectException(ValidationException::class);

        $this->patch(['expected_close_date' => '2026-13-45']);
    }

    public function test_rejects_non_ulid_owner_user_id(): void
    {
        $this->expectException(ValidationException::class);

        $this->patch(['owner_user_id' => str_repeat('x', 300)]);
    }

    /** @param array<string, mixed> $body */
    private function patch(array $body): \Psr\Http\Message\ResponseInterface
    {
        $request = $this->psr17->createServerRequest('PATCH', '/api/v1/deals/' . self::DEAL_ID)
            ->withAttribute(Router::PARAMETERS_ATTRIBUTE, ['dealId' => self::DEAL_ID])
            ->withBody($this->psr17->createStream((string) json_encode($body)));

        return $this->handler->handle($request);
    }
}
