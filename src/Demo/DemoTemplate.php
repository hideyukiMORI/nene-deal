<?php

declare(strict_types=1);

namespace NeneDeal\Demo;

use Nene2\Demo\DemoTemplateKeyInterface;

/**
 * The disposable-demo seed templates Deal offers (`Nene2\Demo` consumer, #69).
 * One well-built pipeline beats several thin ones for this product — Deal's
 * showcase is the funnel-shaped kanban + forecast that `tools/seed-demo.php`
 * already ships for the fixed demo org, so `standard` reuses exactly that
 * dataset ({@see DemoPipelineFixture}). Adding a template means adding a case
 * here, branching on it in {@see DemoDataSeeder}, and listing the exact path
 * in the bearer-middleware blocklist ({@see \NeneDeal\Http\RuntimeServiceProvider})
 * — the route itself (`/demo/{template}`) already matches.
 */
enum DemoTemplate: string implements DemoTemplateKeyInterface
{
    case Standard = 'standard';

    public static function tryFromValue(string $value): ?static
    {
        return self::tryFrom($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
