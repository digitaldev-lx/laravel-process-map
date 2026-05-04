<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Enums\AutomationLevel;

it('maps a heuristic score to a level', function (): void {
    expect(AutomationLevel::fromScore(0))->toBe(AutomationLevel::None);
    expect(AutomationLevel::fromScore(1))->toBe(AutomationLevel::Low);
    expect(AutomationLevel::fromScore(2))->toBe(AutomationLevel::Low);
    expect(AutomationLevel::fromScore(3))->toBe(AutomationLevel::Medium);
    expect(AutomationLevel::fromScore(5))->toBe(AutomationLevel::Medium);
    expect(AutomationLevel::fromScore(6))->toBe(AutomationLevel::High);
    expect(AutomationLevel::fromScore(99))->toBe(AutomationLevel::High);
});
