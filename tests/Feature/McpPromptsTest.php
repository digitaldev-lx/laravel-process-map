<?php

declare(strict_types=1);

use DigitaldevLx\LaravelProcessMap\Mcp\Prompts\AuditProcessPrompt;
use DigitaldevLx\LaravelProcessMap\Mcp\Prompts\DocumentProcessPrompt;
use DigitaldevLx\LaravelProcessMap\Mcp\Prompts\FindAutomationOpportunitiesPrompt;
use DigitaldevLx\LaravelProcessMap\Mcp\Prompts\GenerateTechnicalHandoverPrompt;
use DigitaldevLx\LaravelProcessMap\Mcp\Prompts\PrepareMcpToolsFromActionsPrompt;
use DigitaldevLx\LaravelProcessMap\Mcp\Prompts\RefactorProcessSafelyPrompt;
use Laravel\Mcp\Request;

function renderPrompt(object $prompt, array $args = []): string
{
    $request = new Request;
    $request->setArguments($args);
    $responses = $prompt->handle($request);

    return (string) $responses[0]->content();
}

it('AuditProcessPrompt embeds the process placeholder and the default depth', function (): void {
    $rendered = renderPrompt(app(AuditProcessPrompt::class), ['process' => 'lead-management']);

    expect($rendered)->toContain('process **lead-management**');
    expect($rendered)->toContain('Audit depth: standard');
});

it('RefactorProcessSafelyPrompt accepts overrides and lists every required section', function (): void {
    $rendered = renderPrompt(app(RefactorProcessSafelyPrompt::class), [
        'process' => 'booking',
        'target_style' => 'modules',
        'risk_tolerance' => 'medium',
    ]);

    expect($rendered)->toContain('process **booking**');
    expect($rendered)->toContain('Target architectural style: modules');
    expect($rendered)->toContain('Risk tolerance: medium');
    expect($rendered)->toContain('Rollback plan');
});

it('DocumentProcessPrompt defaults audience to technical', function (): void {
    $rendered = renderPrompt(app(DocumentProcessPrompt::class), ['process' => 'lead']);

    expect($rendered)->toContain('Audience: technical');
});

it('FindAutomationOpportunitiesPrompt enumerates the search heuristics', function (): void {
    $rendered = renderPrompt(app(FindAutomationOpportunitiesPrompt::class));

    expect($rendered)->toContain('manual Artisan commands');
    expect($rendered)->toContain('synchronous notifications');
});

it('GenerateTechnicalHandoverPrompt mentions onboarding notes', function (): void {
    $rendered = renderPrompt(app(GenerateTechnicalHandoverPrompt::class), ['detail_level' => 'exhaustive']);

    expect($rendered)->toContain('Detail level: exhaustive');
    expect($rendered)->toContain('onboarding notes');
});

it('PrepareMcpToolsFromActionsPrompt explicitly marks the unsafe bucket', function (): void {
    $rendered = renderPrompt(app(PrepareMcpToolsFromActionsPrompt::class));

    expect($rendered)->toContain('unsafe / destructive');
    expect($rendered)->toContain('do not expose');
});
