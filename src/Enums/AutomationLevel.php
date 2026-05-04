<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Enums;

/**
 * Coarse classification of how automated a discovered process looks.
 */
enum AutomationLevel: string
{
    case None = 'none';
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    /**
     * Pick the level that matches a heuristic score. The exact thresholds are
     * tuned in `AutomationDetector`; centralising them here keeps callers
     * decoupled from the concrete numbers.
     */
    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 6 => self::High,
            $score >= 3 => self::Medium,
            $score >= 1 => self::Low,
            default => self::None,
        };
    }
}
