<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Tests;

use DigitaldevLx\LaravelProcessMap\Providers\ProcessMapServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ProcessMapServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('process-map.output_path', sys_get_temp_dir().'/process-map-tests');
    }
}
