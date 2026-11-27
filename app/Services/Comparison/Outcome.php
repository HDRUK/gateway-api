<?php

namespace App\Services\Comparison;

/** The call made about one field when two datasets are compared. */
enum Outcome: string
{
    case Identical = 'identical';
    case Similar = 'similar';
    case Different = 'different';
    /** One or both datasets have no value, so there is nothing to compare. */
    case Missing = 'missing';
}
