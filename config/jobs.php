<?php

return [
    'default_timeout' => env('QUEUE_JOB_TIMEOUT', 600), /*10 minutes*/
    'reindex_memory_limit' => env('QUEUE_REINDEX_MEMORY_LIMIT', '512M'),
    'link_check_memory_limit' => env('QUEUE_LINK_CHECK_MEMORY_LIMIT', '512M'),
];
