<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Commands;

use Illuminate\Console\Command;

final class InstallCommand extends Command
{
    protected $signature = 'process-map:install {--scan : Run a first scan immediately after installing}';

    protected $description = 'Install the package: publish the config and create the output directory';

    public function handle(): int
    {
        $this->info('Publishing process-map configuration...');

        $this->call('vendor:publish', [
            '--tag' => 'process-map-config',
        ]);

        $output = (string) config('process-map.output_path', storage_path('app/process-map'));

        if (! is_dir($output) && ! @mkdir($output, 0755, true) && ! is_dir($output)) {
            $this->warn("Could not create output path: {$output}. The package will try again on first scan.");
        } else {
            $this->info("Output path ready: {$output}");
        }

        $this->newLine();
        $this->line('Next steps:');
        $this->line('  - Edit config/process-map.php if your app uses non-default folders.');
        $this->line('  - Run: php artisan process-map:scan --all');

        if ($this->option('scan')) {
            $this->newLine();

            return $this->call('process-map:scan', ['--all' => true]);
        }

        return self::SUCCESS;
    }
}
