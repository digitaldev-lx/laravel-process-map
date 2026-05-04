<?php

declare(strict_types=1);

namespace Tests\Fixtures\App\Models\Concerns;

trait HasUuid
{
    public function uuid(): string
    {
        return 'fake-uuid';
    }
}
