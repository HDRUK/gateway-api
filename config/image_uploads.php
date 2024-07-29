<?php

return [
    "height" => env("UPLOAD_MAX_IMAGE_PIXEL_HEIGHT", 300),
    "width" => env("UPLOAD_MAX_IMAGE_PIXEL_WIDTH", 600),
    "aspect" => env("UPLOAD_IMAGE_ASPECT_RATIO", 2)
];
