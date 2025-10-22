<?php

return [
    "kid" => env("JWT_KID"),
    "secret" => env("JWT_SECRET", 300),
    "expiration" => env("JWT_EXPIRATION", 86400),
];
