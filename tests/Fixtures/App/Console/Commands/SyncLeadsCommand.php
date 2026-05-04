<?php

declare(strict_types=1);

namespace Tests\Fixtures\App\Console\Commands;

use Illuminate\Console\Command;

class SyncLeadsCommand extends Command
{
    protected $signature = 'leads:sync {--since=}';

    protected $description = 'Pull recent leads from the upstream CRM';

    public function handle(): int
    {
        return self::SUCCESS;
    }
}
