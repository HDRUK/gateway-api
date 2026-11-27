<?php

/**
 * Enrichment modules (GAT-9844).
 *
 * The whole layer is behind the `ModuleManager` Pennant flag, which resolves
 * false until it is stored as on. Each module then runs only for the partner
 * contexts and subject types listed here. A module with no partners listed
 * runs for no one, so the HDRUK Gateway gets nothing unless it is added.
 *
 * Each entry: 'key' => ['class' => EnrichmentModule class, 'partners' => [...], 'subjects' => [model classes]].
 */
return [
    'modules' => [
        // CLOSER Discovery summary for PRUK cohort datasets. See App\Modules\Closer\CloserModule
        // for where the CLOSER identifier comes from.
        'closer' => [
            'class' => \App\Modules\Closer\CloserModule::class,
            'partners' => ['PRUK'],
            'subjects' => [\App\Models\Dataset::class],
        ],
    ],
];
