<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    */

    // Apply to all API and Sanctum/JWT routes
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'register', 'logout'],

    'allowed_methods' => ['*'],

    // ❌ DO NOT USE ['*']
    // ✅ Use the EXACT URL of your frontend
    'allowed_origins' => ['https://saby-tinh.vercel.app', 'http://localhost:3000'], 
    
    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // IMPORTANT: Set to true if you are using cookies/sessions for auth
    'supports_credentials' => true,

];