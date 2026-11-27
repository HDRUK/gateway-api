<?php

/*
| POST /api/v2/datasets/compare scores each metadata field of two datasets from
| 0 (nothing in common) to 1 (the same), by a method suited to the field's type
| (App\Services\Comparison\FieldComparator). A score of 1 is "identical"; one
| at or above the boundary below is "similar"; anything lower is "different".
| The boundaries are returned with every response so a client can explain a call.
*/

return [
    // The most datasets one comparison takes (at least two). The first is the
    // reference the others are compared with. Keep the client's
    // NEXT_PUBLIC_COMPARE_MAX_DATASETS the same.
    'max_datasets' => (int) env('COMPARISON_MAX_DATASETS', 4),

    'thresholds' => [
        'text' => 0.5,   // cosine similarity of word counts
        'list' => 0.5,   // shared items over all distinct items (Jaccard)
        'range' => 0.5,  // overlap over combined span
        'count' => 0.8,  // smaller over larger: within a fifth of each other
        'exact' => 1.0,  // all or nothing: no similar band
    ],
];
