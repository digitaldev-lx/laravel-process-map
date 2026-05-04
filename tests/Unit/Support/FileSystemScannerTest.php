<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Support\FileSystemScanner;

beforeEach(function (): void {
    $this->base = sys_get_temp_dir().'/process-map-fs-'.uniqid();
    mkdir($this->base.'/app/Models', 0777, true);
    mkdir($this->base.'/app/Actions', 0777, true);

    file_put_contents($this->base.'/app/Models/Lead.php', '<?php');
    file_put_contents($this->base.'/app/Models/Booking.php', '<?php');
    file_put_contents($this->base.'/app/Models/notes.txt', 'ignored');
    file_put_contents($this->base.'/app/Actions/CreateLeadAction.php', '<?php');
});

afterEach(function (): void {
    exec('rm -rf '.escapeshellarg($this->base));
});

it('iterates over PHP files in the given directories only', function (): void {
    $scanner = new FileSystemScanner;

    $files = iterator_to_array($scanner->findPhpFiles([
        $this->base.'/app/Models',
        $this->base.'/app/Actions',
    ]), false);

    $names = array_map(static fn ($f) => $f->getFilename(), $files);
    sort($names);

    expect($names)->toBe(['Booking.php', 'CreateLeadAction.php', 'Lead.php']);
});

it('silently skips missing directories', function (): void {
    $scanner = new FileSystemScanner;

    $files = iterator_to_array($scanner->findPhpFiles([
        $this->base.'/does-not-exist',
        $this->base.'/app/Models',
    ]), false);

    expect(count($files))->toBe(2);
});

it('returns an empty generator when no directories exist', function (): void {
    $scanner = new FileSystemScanner;

    $files = iterator_to_array($scanner->findPhpFiles([
        $this->base.'/missing-1',
        $this->base.'/missing-2',
    ]), false);

    expect($files)->toBe([]);
});
