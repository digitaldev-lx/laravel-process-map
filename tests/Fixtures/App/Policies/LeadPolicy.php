<?php

declare(strict_types=1);

namespace Tests\Fixtures\App\Policies;

class LeadPolicy
{
    public function viewAny(mixed $user): bool
    {
        return true;
    }

    public function update(mixed $user, mixed $lead): bool
    {
        return true;
    }

    public function assignToAgent(mixed $user, mixed $lead): bool
    {
        return false;
    }
}
