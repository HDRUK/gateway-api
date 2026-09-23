<?php

namespace App\Services;

use Google\Cloud\BigQuery\BigQueryClient;
use Google\Cloud\BigQuery\BigNumeric;
use Google\Cloud\BigQuery\Date;
use Google\Cloud\BigQuery\Numeric;
use Google\Cloud\BigQuery\Time;
use Google\Cloud\BigQuery\Timestamp;

class BigQueryService
{
    protected BigQueryClient $client;

    public function __construct()
    {
        $this->client = new BigQueryClient([
            'projectId'   => config('services.googlebigquery.project_id'),
        ]);
    }

    public function query(string $sql, array $params = []): array
    {
        $queryConfig = $this->client->query($sql);

        if (!empty($params)) {
            $queryConfig->parameters($params);
        }

        $results = $this->client->runQuery($queryConfig);

        $rows = [];
        foreach ($results as $row) {
            $rows[] = array_map(function ($value) {
                return match (true) {
                    $value instanceof Date       => $value->formatAsString(),
                    $value instanceof Time       => $value->formatAsString(),
                    $value instanceof Timestamp  => $value->get()->format('Y-m-d H:i:s'),
                    $value instanceof Numeric    => (float) $value->get(),
                    $value instanceof BigNumeric => (float) $value->get(),
                    is_int($value)               => (int) $value,
                    is_float($value)             => (float) $value,
                    is_numeric($value)           => str_contains($value, '.') ? (float) $value : (int) $value,
                    default                      => $value,
                };
            }, $row);
        }

        return $rows;
    }

    /**
     * Streams a single row into a BigQuery table. Row-level failures are not
     * thrown by the client library itself (only transport/auth errors are),
     * they come back on the InsertResponse, so that's checked here and turned
     * into an exception.
     *
     * @throws \RuntimeException when the insert is rejected by BigQuery
     */
    public function insertRow(string $dataset, string $table, array $row, ?string $insertId = null): void
    {
        $options = $insertId !== null ? ['insertId' => $insertId] : [];

        $response = $this->client->dataset($dataset)->table($table)->insertRow($row, $options);

        if (! $response->isSuccessful()) {
            throw new \RuntimeException(
                "Failed to insert row into {$dataset}.{$table}: ".json_encode($response->failedRows())
            );
        }
    }
}
