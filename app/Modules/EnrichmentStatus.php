<?php

namespace App\Modules;

enum EnrichmentStatus: string
{
    /** Data was fetched. */
    case Ok = 'ok';

    /** The source was reached and had nothing for this subject. */
    case Empty = 'empty';

    /** The last refresh failed; the payload is from the previous good pull. */
    case Stale = 'stale';

    /** The last refresh failed and there is no earlier data. */
    case Failed = 'failed';

    /** The module doesn't apply to this subject. */
    case Skipped = 'skipped';

    /** Nothing has been fetched yet. */
    case Missing = 'missing';
}
