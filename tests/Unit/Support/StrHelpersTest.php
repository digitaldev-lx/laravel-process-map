<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Support\StrHelpers;

it('strips known business suffixes', function (): void {
    $suffixes = ['Action', 'Job', 'Command'];

    expect(StrHelpers::stripBusinessSuffix('CreateLeadAction', $suffixes))->toBe('CreateLead');
    expect(StrHelpers::stripBusinessSuffix('SendInvoiceJob', $suffixes))->toBe('SendInvoice');
    expect(StrHelpers::stripBusinessSuffix('Lead', $suffixes))->toBe('Lead');
});

it('does not strip when only suffix matches the whole class name', function (): void {
    expect(StrHelpers::stripBusinessSuffix('Action', ['Action']))->toBe('Action');
});

it('strips leading verbs only when followed by a StudlyCased remainder', function (): void {
    $verbs = ['create', 'update', 'delete'];

    expect(StrHelpers::stripLeadingVerb('CreateLead', $verbs))->toBe('Lead');
    expect(StrHelpers::stripLeadingVerb('UpdateBookingStatus', $verbs))->toBe('BookingStatus');
    expect(StrHelpers::stripLeadingVerb('Created', $verbs))->toBe('Created');
    expect(StrHelpers::stripLeadingVerb('Lead', $verbs))->toBe('Lead');
});

it('humanises StudlyCased identifiers', function (): void {
    expect(StrHelpers::humanize('LeadManagement'))->toBe('Lead Management');
    expect(StrHelpers::humanize('Booking'))->toBe('Booking');
    expect(StrHelpers::humanize('XMLParser'))->toBe('XML Parser');
});

it('produces deterministic mermaid-safe identifiers', function (): void {
    expect(StrHelpers::safeMermaidId('App\\Actions\\CreateLeadAction'))
        ->toBe('App_Actions_CreateLeadAction');

    expect(StrHelpers::safeMermaidId('123Lead'))
        ->toBe('n_123Lead');

    expect(StrHelpers::safeMermaidId(''))->toStartWith('node_');
});

it('pluralises common english nouns', function (): void {
    expect(StrHelpers::pluralise('Lead'))->toBe('Leads');
    expect(StrHelpers::pluralise('Category'))->toBe('Categories');
    expect(StrHelpers::pluralise('Box'))->toBe('Boxes');
    expect(StrHelpers::pluralise(''))->toBe('');
});
