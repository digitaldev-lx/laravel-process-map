<?php

declare(strict_types=1);

namespace Tests\Fixtures\App\Http\Controllers;

use Tests\Fixtures\App\Actions\CreateLeadAction;
use Tests\Fixtures\App\Events\LeadCreated;
use Tests\Fixtures\App\Http\Requests\StoreLeadRequest;
use Tests\Fixtures\App\Models\Lead;

class LeadController
{
    public function __construct(private readonly CreateLeadAction $action) {}

    public function index(): array
    {
        return ['leads' => []];
    }

    public function store(StoreLeadRequest $request): Lead
    {
        $lead = ($this->action)($request->validated());

        event(new LeadCreated($lead));

        return $lead;
    }

    public function destroy(Lead $lead): bool
    {
        return (bool) $lead->delete();
    }

    private function audit(): void
    {
        // hidden helper, should not appear in the public method list
    }
}
