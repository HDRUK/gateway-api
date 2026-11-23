<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A Typesense reindex that stopped without changing what search serves:
 * documents rejected on import, a schema that no longer matches, or another
 * reindex of the same entity already running.
 */
class TypesenseReindexException extends RuntimeException
{
}
