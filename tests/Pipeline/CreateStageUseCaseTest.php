<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Pipeline;

use NeneDeal\Pipeline\CreateStageInput;
use NeneDeal\Pipeline\CreateStageUseCase;
use NeneDeal\Pipeline\PipelineStage;
use NeneDeal\Pipeline\SlugAlreadyTakenException;
use NeneDeal\Tests\Support\FixedClock;
use NeneDeal\Tests\Support\InMemoryPipelineStageRepository;
use NeneDeal\Tests\Support\StubCurrentOrganization;
use PHPUnit\Framework\TestCase;

final class CreateStageUseCaseTest extends TestCase
{
    private const ORG_ID = '01ORG00000000000000000000A';

    private InMemoryPipelineStageRepository $stages;
    private CreateStageUseCase $useCase;

    protected function setUp(): void
    {
        $this->stages = new InMemoryPipelineStageRepository();
        $this->useCase = new CreateStageUseCase($this->stages, new StubCurrentOrganization(self::ORG_ID), new FixedClock());
    }

    public function test_creates_stage_with_derived_slug(): void
    {
        $stage = $this->useCase->execute(new CreateStageInput('Initial Meeting', 10));

        self::assertSame('initial-meeting', $stage->slug);
        self::assertSame('Initial Meeting', $stage->label);
        self::assertSame(10, $stage->sortOrder);
        self::assertFalse($stage->isTerminal);
        self::assertFalse($stage->isWon);
        self::assertSame(self::ORG_ID, $stage->organizationId);
    }

    #[\PHPUnit\Framework\Attributes\TestWith(['  Initial Meeting  ', 'initial-meeting'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['With/Slashes', 'with-slashes'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['ALL CAPS', 'all-caps'])]
    public function test_slug_derivation_trims_normalises_and_lowercases(string $label, string $expectedSlug): void
    {
        $stage = $this->useCase->execute(new CreateStageInput($label, 1));

        self::assertSame($expectedSlug, $stage->slug);
    }

    public function test_sort_order_zero_is_valid(): void
    {
        $stage = $this->useCase->execute(new CreateStageInput('First', 0));

        self::assertSame(0, $stage->sortOrder);
    }

    public function test_new_stage_is_never_terminal_or_won(): void
    {
        $stage = $this->useCase->execute(new CreateStageInput('My Stage', 5));

        self::assertFalse($stage->isTerminal);
        self::assertFalse($stage->isWon);
    }

    public function test_label_case_differs_from_existing_slug_still_conflicts(): void
    {
        $this->stages->save(new PipelineStage('01STAGEEXISTING00000000001', self::ORG_ID, 'proposal', 'Proposal', 3, false, false));

        // "PROPOSAL" derives slug "proposal", which already exists
        $this->expectException(SlugAlreadyTakenException::class);
        $this->useCase->execute(new CreateStageInput('PROPOSAL', 5));
    }

    public function test_throws_when_slug_already_taken(): void
    {
        $this->stages->save(new PipelineStage(
            '01STAGEEXISTING00000000001',
            self::ORG_ID,
            'proposal',
            'Proposal',
            3,
            false,
            false,
        ));

        $this->expectException(SlugAlreadyTakenException::class);
        $this->useCase->execute(new CreateStageInput('Proposal', 5));
    }
}
