<?php

declare(strict_types=1);

namespace DigitaldevLx\LaravelProcessMap\Mcp\Tools;

use DigitaldevLx\LaravelProcessMap\ProcessMap;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Title('Refresh process map')]
#[Description('Re-runs the static scan and rewrites the configured exports (JSON / Markdown / Mermaid). Read-only with respect to the analysed application; only writes inside the configured output_path.')]
#[IsReadOnly]
#[IsIdempotent]
final class RefreshProcessMapTool extends AbstractProcessMapTool
{
    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'exports' => $schema->array()
                ->description('Subset of [json, markdown, mermaid] to regenerate. Defaults to all three.'),
            'include_routes' => $schema->boolean()->default(true),
            'include_process_detection' => $schema->boolean()->default(true),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function run(Request $request): array
    {
        $this->guard->ensureRefreshAllowed();

        $config = app('config');
        $original = [
            'routes' => $config->get('process-map.scan.routes'),
            'detection' => $config->get('process-map.process_detection.enabled'),
        ];

        if ($request->get('include_routes', true) === false) {
            $config->set('process-map.scan.routes', false);
        }

        if ($request->get('include_process_detection', true) === false) {
            $config->set('process-map.process_detection.enabled', false);
        }

        try {
            /** @var ProcessMap $manager */
            $manager = app(ProcessMap::class);
            $result = $manager->scan();

            $exports = (array) $request->get('exports', ['json', 'markdown', 'mermaid']);
            $exports = array_values(array_filter($exports, 'is_string'));

            if ($exports === []) {
                $exports = ['json', 'markdown', 'mermaid'];
            }

            $outputs = [];
            $output = (string) $config->get('process-map.output_path', storage_path('app/process-map'));

            if (in_array('json', $exports, true)) {
                $manager->exportJson($result, $output.'/process-map.json');
                $outputs['json'] = $output.'/process-map.json';
            }

            if (in_array('markdown', $exports, true)) {
                $manager->exportMarkdown($result, $output.'/process-map.md');
                $outputs['markdown'] = $output.'/process-map.md';
            }

            if (in_array('mermaid', $exports, true)) {
                $manager->exportMermaid($result, $output.'/process-map.mmd');
                $outputs['mermaid'] = $output.'/process-map.mmd';
            }

            $this->repository->flush();
        } finally {
            $config->set('process-map.scan.routes', $original['routes']);
            $config->set('process-map.process_detection.enabled', $original['detection']);
        }

        return [
            'success' => true,
            'generated_at' => $result->generatedAt,
            'summary' => $result->summary->toArray(),
            'outputs' => $outputs,
        ];
    }
}
