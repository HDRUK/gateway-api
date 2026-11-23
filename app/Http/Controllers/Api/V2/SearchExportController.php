<?php

namespace App\Http\Controllers\Api\V2;

use App\Exports\DatasetListExport;
use App\Exports\DatasetTableExport;
use App\Exports\DataUseExport;
use App\Exports\ToolListExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SearchExport;
use App\SearchProviders\HDRUK;
use Auditor;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SearchExportController extends Controller
{
    private const EXPORT_LIMIT = 100;

    public function export(SearchExport $request, string $type): BinaryFileResponse
    {
        $rows = app(HDRUK::class)->export(
            $request->input('query') ?? '',
            $type,
            $request->except(['query', 'type', 'download_type']),
            self::EXPORT_LIMIT
        );

        $export = match ($type) {
            'datasets' => $request->input('download_type') === 'table'
                ? new DatasetTableExport($rows)
                : new DatasetListExport($rows),
            'dur'      => new DataUseExport($rows),
            'tools'    => new ToolListExport($rows),
        };

        Auditor::log([
            'action_type' => 'POST',
            'action_name' => class_basename($this) . '@' . __FUNCTION__,
            'description' => "Search {$type} export",
        ]);

        return Excel::download($export, "{$type}.csv");
    }
}
