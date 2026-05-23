<?php

/**
 * SECURITY FIX: explicit CORS configuration.
 *
 * Without this file, Laravel's HandleCors middleware falls back to allowing
 * all origins ('*'), which lets any website make credentialed cross-origin
 * requests to the API.
 *
 * Tune `allowed_origins` and `allowed_origins_patterns` for each environment
 * via .env:
 *   CORS_ALLOWED_ORIGINS=https://app.example.com,https://admin.example.com
 *
 * For local development, CORS_ALLOWED_ORIGINS can be set to http://localhost:3000
 * etc. in .env.local — never set it to '*' in production.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    /*
     * List of allowed origins.  Read from CORS_ALLOWED_ORIGINS (comma-separated).
     * Defaults to an empty list — which means no cross-origin requests are allowed
     * unless explicitly configured.
     */
    'allowed_origins' => array_filter(
        array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', '')))
    ),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization', 'Accept', 'X-XSRF-TOKEN'],

    'exposed_headers' => [],

    'max_age' => 0,

    /*
     * Credentials (cookies, Authorization headers) are only sent with
     * same-origin requests unless the browser has received an explicit
     * Access-Control-Allow-Credentials: true header from the server.
     * Set to true if your frontend is on a different origin and uses
     * session cookies or Bearer tokens.
     */
    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', false),

];
