<?php

declare(strict_types=1);

namespace Tests\Fixtures\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessHeavyReportJob implements ShouldQueue
{
    use Queueable;

    public string $queue = 'reports';

    public int $tries = 5;

    public int $timeout = 120;

    public int $backoff = 30;

    public function handle(): void {}
}
