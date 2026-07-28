<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\V2\DatasetController;
use App\Http\Requests\V2\Dataset\GetDataset;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Invokes the exact same code path as GET /api/v2/datasets/{id}
 * (Api\V2\DatasetController@showActive) for a single dataset id, so the
 * translated/prepared output can be inspected from the CLI.
 *
 * It does this by resolving the real controller from the container and
 * binding a synthetic request, so PartnerContext / OutputSchemaContext
 * (which read request headers + query) resolve identically to an HTTP call.
 */
class ShowDatasetV2 extends Command
{
    protected $signature = 'app:show-dataset-v2
                            {id : The dataset id (integer, same as the {id} route param)}
                            {--partner= : x-partner-context header (defaults to config/partners.php default)}
                            {--schema-model= : schema_model query param / x-schema-model header}
                            {--schema-version= : schema_version query param / x-schema-version header}
                            {--export= : export query param (e.g. structuralMetadata)}
                            {--output= : Write the JSON response to this file instead of stdout}';

    protected $description = 'Run the V2 GET /datasets/{id} (showActive) code path for a dataset id and dump the result';

    public function handle(): int
    {
        $id = (int) $this->argument('id');

        // Build query params exactly as the controller reads them.
        $query = array_filter([
            'schema_model' => $this->option('schema-model'),
            'schema_version' => $this->option('schema-version'),
            'export' => $this->option('export'),
        ], fn ($v) => $v !== null && $v !== '');

        $request = Request::create("/api/v2/datasets/{$id}", 'GET', $query);

        // Headers the context classes look for (request()->header(...)).
        if ($partner = $this->option('partner')) {
            $request->headers->set('x-partner-context', $partner);
        }
        if ($model = $this->option('schema-model')) {
            $request->headers->set('x-schema-model', $model);
        }
        if ($version = $this->option('schema-version')) {
            $request->headers->set('x-schema-version', $version);
        }

        // Bind a route so request()->route('id') resolves like it would over HTTP.
        $route = (new Route('GET', '/api/v2/datasets/{id}', []))->bind($request);
        $route->setParameter('id', $id);
        $request->setRouteResolver(fn () => $route);

        // Swap in our request so PartnerContext/OutputSchemaContext see it, then restore.
        $previous = $this->laravel->bound('request') ? $this->laravel->make('request') : null;
        $this->laravel->instance('request', $request);

        try {
            /** @var GetDataset $getDataset */
            $getDataset = GetDataset::createFrom($request);
            $getDataset->setContainer($this->laravel)->setRouteResolver(fn () => $route);

            /** @var DatasetController $controller */
            $controller = $this->laravel->make(DatasetController::class);

            $response = $controller->showActive($getDataset, $id);
        } catch (\Throwable $e) {
            $this->error('showActive threw: '.$e->getMessage());
            $this->line($e->getFile().':'.$e->getLine());

            return self::FAILURE;
        } finally {
            if ($previous !== null) {
                $this->laravel->instance('request', $previous);
            }
        }

        if ($response instanceof BinaryFileResponse) {
            $this->info('Response is a file export ('.$response->getFile()->getPathname().') — not printable as JSON.');

            return self::SUCCESS;
        }

        $status = $response->getStatusCode();
        $payload = $response instanceof JsonResponse
            ? $response->getData(true)
            : json_decode($response->getContent() ?: 'null', true);

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($outputPath = $this->option('output')) {
            file_put_contents($outputPath, $json.PHP_EOL);
            $this->info("HTTP {$status} — written to {$outputPath}");
        } else {
            $this->line("HTTP {$status}");
            $this->line($json);
        }

        return $status >= 200 && $status < 300 ? self::SUCCESS : self::FAILURE;
    }
}
