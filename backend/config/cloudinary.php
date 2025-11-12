<?php

// amazon-q-ignore[CWE-352,1275] - False positive: Config files are server-side only
return [
    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
    'api_key' => env('CLOUDINARY_KEY'),
    'api_secret' => env('CLOUDINARY_SECRET'),
    'url' => [
        'secure' => true
    ]
];
