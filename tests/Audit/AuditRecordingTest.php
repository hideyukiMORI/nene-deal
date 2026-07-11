<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Audit;

use Nene2\Auth\LocalBearerTokenVerifier;
use NeneDeal\Audit\AuditAction;
use NeneDeal\Auth\InvalidCredentialsException;
use NeneDeal\Auth\LoginInput;
use NeneDeal\Auth\LoginUseCase;
use NeneDeal\Deal\ChangeDealStageUseCase;
use NeneDeal\Deal\CreateDealInput;
use NeneDeal\Deal\CreateDealUseCase;
use NeneDeal\Deal\DeleteDealUseCase;
use NeneDeal\Deal\RestoreDealUseCase;
use NeneDeal\Deal\UpdateDealInput;
use NeneDeal\Deal\UpdateDealUseCase;
use NeneDeal\Handoff\InvoiceHandoffUseCase;
use NeneDeal\Pipeline\CreateStageInput;
use NeneDeal\Pipeline\CreateStageUseCase;
use NeneDeal\Pipeline\DeleteStageUseCase;
use NeneDeal\Pipeline\PipelineStage;
use NeneDeal\Pipeline\UpdateStageInput;
use NeneDeal\Pipeline\UpdateStageUseCase;
use NeneDeal\Tests\Support\FakeInvoiceClient;
use NeneDeal\Tests\Support\FixedClock;
use NeneDeal\Tests\Support\InMemoryDealRepository;
use NeneDeal\Tests\Support\InMemoryPipelineStageRepository;
use NeneDeal\Tests\Support\InMemoryUserRepository;
use NeneDeal\Tests\Support\RecordingAuditRecorder;
use NeneDeal\Tests\Support\StubCurrentOrganization;
use NeneDeal\User\CreateUserInput;
use NeneDeal\User\CreateUserUseCase;
use NeneDeal\User\DeleteUserUseCase;
use NeneDeal\User\OperatorRole;
use NeneDeal\User\UpdateUserInput;
use NeneDeal\User\UpdateUserUseCase;
use NeneDeal\User\User;
use NeneDeal\User\UserStatus;
use PHPUnit\Framework\TestCase;

/**
 * Pins the #89 hard rule: every mutation use case records a `Nene2\Audit`
 * event with the product action vocabulary, actor and organization scope.
 */
final class AuditRecordingTest extends TestCase
{
    private const ORG_ID = '01ORG00000000000000000000A';
    private const ACTOR_ID = '01USERADMIN000000000000001';

    private RecordingAuditRecorder $audit;
    private StubCurrentOrganization $org;
    private InMemoryDealRepository $deals;
    private InMemoryPipelineStageRepository $stages;

    protected function setUp(): void
    {
        $this->audit = new RecordingAuditRecorder();
        $this->org = new StubCurrentOrganization(self::ORG_ID);
        $this->deals = new InMemoryDealRepository();
        $this->stages = new InMemoryPipelineStageRepository([
            new PipelineStage('01STAGELEAD00000000000000A', self::ORG_ID, 'lead', 'Lead', 1, false, false),
            new PipelineStage('01STAGEWON000000000000000A', self::ORG_ID, 'won', 'Won', 6, true, true),
        ]);
    }

    private function createDeal(): string
    {
        $useCase = new CreateDealUseCase($this->deals, $this->stages, $this->audit, $this->org);

        return $useCase->execute(
            new CreateDealInput(accountLabel: 'Acme', amountCents: 100_000, stageRef: 'lead', probabilityPercent: 50),
            self::ACTOR_ID,
        )->id;
    }

    public function test_deal_create_records_an_audit_event_with_actor_and_org(): void
    {
        $dealId = $this->createDeal();

        $event = $this->audit->lastEvent();
        self::assertNotNull($event);
        self::assertSame(AuditAction::DEAL_CREATED, $event->action);
        self::assertSame('deal', $event->entityType);
        self::assertSame($dealId, $event->entityId);
        self::assertSame(self::ACTOR_ID, $event->actorId);
        self::assertSame(self::ORG_ID, $event->organizationId);
        self::assertIsArray($event->after);
        self::assertSame('Acme', $event->after['account_label']);
    }

    public function test_deal_update_records_changed_fields_only(): void
    {
        $dealId = $this->createDeal();

        $useCase = new UpdateDealUseCase($this->deals, $this->audit, $this->org);
        $useCase->execute($dealId, new UpdateDealInput(amountCents: 200_000), self::ACTOR_ID);

        $event = $this->audit->lastEvent();
        self::assertNotNull($event);
        self::assertSame(AuditAction::DEAL_UPDATED, $event->action);
        self::assertSame(['amount_cents' => 100_000], $event->before);
        self::assertSame(['amount_cents' => 200_000], $event->after);
    }

    public function test_deal_stage_change_delete_and_restore_record_events(): void
    {
        $dealId = $this->createDeal();

        (new ChangeDealStageUseCase($this->deals, $this->stages, $this->audit, $this->org))
            ->execute($dealId, 'won', self::ACTOR_ID);
        (new DeleteDealUseCase($this->deals, $this->audit, $this->org))->execute($dealId, self::ACTOR_ID);
        (new RestoreDealUseCase($this->deals, $this->audit, $this->org))->execute($dealId, self::ACTOR_ID);

        self::assertSame([
            AuditAction::DEAL_CREATED,
            AuditAction::DEAL_STAGE_CHANGED,
            AuditAction::DEAL_DELETED,
            AuditAction::DEAL_RESTORED,
        ], $this->audit->actions());
    }

    public function test_invoice_handoff_records_link_ids(): void
    {
        $dealId = $this->createDeal();
        (new ChangeDealStageUseCase($this->deals, $this->stages, $this->audit, $this->org))
            ->execute($dealId, 'won', self::ACTOR_ID);

        $useCase = new InvoiceHandoffUseCase(
            $this->deals,
            $this->stages,
            new FakeInvoiceClient(),
            new FixedClock(),
            $this->audit,
            $this->org,
        );
        $useCase->execute($dealId, self::ACTOR_ID);

        $event = $this->audit->lastEvent();
        self::assertNotNull($event);
        self::assertSame(AuditAction::DEAL_INVOICE_HANDOFF, $event->action);
        self::assertSame(['invoice_client_id' => 4821, 'invoice_quote_id' => 9930], $event->after);
        self::assertSame(self::ORG_ID, $event->organizationId);
    }

    public function test_stage_lifecycle_records_events(): void
    {
        $create = new CreateStageUseCase($this->stages, $this->org, new FixedClock(), $this->audit);
        $stage = $create->execute(new CreateStageInput('Proposal', 3), self::ACTOR_ID);

        (new UpdateStageUseCase($this->stages, $this->audit))
            ->execute($stage->id, new UpdateStageInput(label: 'Proposal v2'), self::ACTOR_ID);
        (new DeleteStageUseCase($this->stages, $this->audit))->execute($stage->id, self::ACTOR_ID);

        self::assertSame([
            AuditAction::STAGE_CREATED,
            AuditAction::STAGE_UPDATED,
            AuditAction::STAGE_DELETED,
        ], $this->audit->actions());
        self::assertSame(self::ORG_ID, $this->audit->lastEvent()?->organizationId);
    }

    public function test_user_lifecycle_records_events_without_password_material(): void
    {
        $users = new InMemoryUserRepository();
        $create = new CreateUserUseCase($users, $this->org, new FixedClock(), $this->audit);
        $user = $create->execute(new CreateUserInput('op@example.com', 'password123', OperatorRole::Operator), self::ACTOR_ID);

        (new UpdateUserUseCase($users, $this->org, $this->audit))
            ->execute($user->id, self::ACTOR_ID, new UpdateUserInput(status: UserStatus::Disabled));
        (new DeleteUserUseCase($users, $this->org, $this->audit))->execute($user->id, self::ACTOR_ID);

        self::assertSame([
            AuditAction::USER_CREATED,
            AuditAction::USER_UPDATED,
            AuditAction::USER_DELETED,
        ], $this->audit->actions());

        foreach ($this->audit->events as $event) {
            $payload = json_encode([$event->before, $event->after, $event->metadata]);
            self::assertIsString($payload);
            self::assertStringNotContainsString('password', $payload);
        }

        // The status change snapshot carries the disable transition.
        self::assertSame(['status' => 'active'], $this->audit->events[1]->before);
        self::assertSame(['status' => 'disabled'], $this->audit->events[1]->after);
    }

    public function test_login_success_and_failure_record_events(): void
    {
        $users = new InMemoryUserRepository();
        $users->add(new User(
            id: '01USEROPERATOR0000000000AA',
            organizationId: self::ORG_ID,
            email: 'operator@nene-deal.test',
            passwordHash: password_hash('password', PASSWORD_DEFAULT),
            role: OperatorRole::Operator,
        ));

        $clock = new FixedClock();
        $useCase = new LoginUseCase($users, new LocalBearerTokenVerifier('test-secret', $clock), $clock, $this->audit);

        $useCase->execute(new LoginInput('operator@nene-deal.test', 'password'));

        try {
            $useCase->execute(new LoginInput('nobody@nene-deal.test', 'wrong'));
            self::fail('Expected InvalidCredentialsException.');
        } catch (InvalidCredentialsException) {
            // Expected.
        }

        self::assertSame([AuditAction::LOGIN_SUCCEEDED, AuditAction::LOGIN_FAILED], $this->audit->actions());

        $succeeded = $this->audit->events[0];
        self::assertSame(self::ORG_ID, $succeeded->organizationId);
        self::assertSame('01USEROPERATOR0000000000AA', $succeeded->actorId);

        // Failure events carry no org/actor so account existence is not disclosed.
        $failed = $this->audit->events[1];
        self::assertNull($failed->organizationId);
        self::assertNull($failed->actorId);
        self::assertSame(['email' => 'nobody@nene-deal.test', 'failure_reason' => 'invalid_credentials'], $failed->after);
    }
}
