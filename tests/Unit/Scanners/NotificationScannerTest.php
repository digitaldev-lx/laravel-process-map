<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Enums\ScannerType;
use DigitaldevLx\LaravelProcessMap\Scanners\NotificationScanner;
use DigitaldevLx\LaravelProcessMap\Support\ClassNameResolver;
use DigitaldevLx\LaravelProcessMap\Support\FileSystemScanner;
use DigitaldevLx\LaravelProcessMap\Support\NamespaceResolver;

beforeEach(function (): void {
    $this->scanner = new NotificationScanner(
        new NamespaceResolver,
        new ClassNameResolver,
        new FileSystemScanner,
        [__DIR__.'/../../Fixtures/App/Notifications'],
    );
});

it('discovers notifications and resolves their channels and delivery methods', function (): void {
    $classes = $this->scanner->scan();

    expect($classes)->toHaveCount(1);

    $notification = $classes[0];

    expect($notification->type)->toBe(ScannerType::Notification);
    expect($notification->shortName)->toBe('LeadAssignedNotification');
    expect($notification->metadata['channels'])->toBe(['mail', 'database']);
    expect($notification->metadata['should_queue'])->toBeTrue();
    expect($notification->metadata['delivery_methods'])->toContain('toMail', 'toDatabase');
});

it('does not pick up generic toString/toArray methods as delivery channels (M3)', function (): void {
    $tmp = sys_get_temp_dir().'/process-map-notif-fixture-'.uniqid();
    mkdir($tmp.'/Notifications', 0777, true);

    file_put_contents($tmp.'/Notifications/PlainNotification.php', <<<'PHP'
        <?php
        namespace TestFixturesPlain\Notifications;

        class PlainNotification
        {
            public function via($notifiable): array { return ['mail']; }
            public function toMail($notifiable) {}
            public function toString(): string { return 'fake'; }
            public function toArray(): array { return []; }
        }
        PHP);

    $scanner = new NotificationScanner(
        new NamespaceResolver,
        new ClassNameResolver,
        new FileSystemScanner,
        [$tmp.'/Notifications'],
    );

    $found = $scanner->scan();

    expect($found)->toHaveCount(1);
    expect($found[0]->metadata['delivery_methods'])->toBe(['toMail']);

    exec('rm -rf '.escapeshellarg($tmp));
});
