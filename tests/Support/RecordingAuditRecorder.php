<?php

declare(strict_types=1);

namespace NeneDeal\Tests\Support;

use Nene2\Audit\AuditEvent;
use Nene2\Audit\AuditRecorderInterface;

/** Collects recorded audit events for assertions. */
final class RecordingAuditRecorder implements AuditRecorderInterface
{
    /** @var list<AuditEvent> */
    public array $events = [];

    public function record(AuditEvent $event): void
    {
        $this->events[] = $event;
    }

    public function lastEvent(): ?AuditEvent
    {
        return $this->events !== [] ? $this->events[array_key_last($this->events)] : null;
    }

    /** @return list<string> */
    public function actions(): array
    {
        return array_map(static fn (AuditEvent $e): string => $e->action, $this->events);
    }
}
