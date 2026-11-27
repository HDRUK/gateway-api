<?php

namespace App\Services\Comparison;

/** What kind of value a field holds, which decides how two values are compared. */
enum FieldType: string
{
    /** Free text, such as an abstract. */
    case Text = 'text';
    /** An unordered set of terms or codes, such as keywords. */
    case List = 'list';
    /** A span of numbers, such as an age range. */
    case Range = 'range';
    /** A quantity, such as a population size. */
    case Count = 'count';
    /** Identifiers, dates, yes/no and fixed choices: the same or not. */
    case Exact = 'exact';
}
