<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Enums;

enum RiskLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
}
