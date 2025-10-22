<?php

return [
    "jwt_secret" => env("JWT_SECRET", 300),
    "jwt_expiration" => env("JWT_EXPIRATION", 86400),
];
