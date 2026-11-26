<?php

/**
 * Enrichment modules (GAT-9844).
 *
 * The whole layer is behind the `ModuleManager` Pennant flag, which resolves
 * false until it is stored as on. Each module then runs only for the partner
 * contexts and subject types listed here. A module with no partners listed
 * runs for no one, so the HDRUK Gateway gets nothing unless it is added.
 *
 * Example:
 *   'closer' => [
 *       'class'    => \App\Modules\Closer\CloserModule::class,
 *       'partners' => ['PRUK'],
 *       'subjects' => [\App\Models\Dataset::class],
 *   ],
 */
return [
    'modules' => [
        //
    ],
];
