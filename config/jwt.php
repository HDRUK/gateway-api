<?php

return [
    "kid" => env("JWT_KID", "jwtkidnotfound"),
    "secret" => env("JWT_SECRET", ""),
    "expiration" => env("JWT_EXPIRATION", 86400),
];
