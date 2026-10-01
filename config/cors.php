<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing
|--------------------------------------------------------------------------
|
| This site has no public API, so CORS is switched off on purpose: no path is listed.
| (Without this file Laravel's default would enable CORS for "api/*" with any origin.)
| If an API is ever added, list its paths and allow only this site's own origin.
|
*/

return [
    'paths' => [],
    'allowed_methods' => ['GET'],
    'allowed_origins' => [],
    'allowed_origins_patterns' => [],
    'allowed_headers' => [],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
