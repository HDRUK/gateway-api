<?php

return [
    "kid" => env("JWT_KID", "jwtkidnotfound"),
    // No fallback by design. The previous default of 300 meant an unset
    // JWT_SECRET silently signed every token with the string "300". An
    // entirely guessable key, with nothing in the logs to say so.
    // An absent secret must fail clearly instead; see JwtController.
    "secret" => env("JWT_SECRET"),
    "expiration" => env("JWT_EXPIRATION", 86400),
];
