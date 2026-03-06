<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

// Health check (no auth)
$router->get('/api/v1/health', function () {
    return response()->json(['status' => 'ok', 'service' => 'egyptian-id-api']);
});

// Egyptian National ID validate & extract (API key required, rate limited, tracked)
$router->group([
    'prefix' => 'api/v1/national-id',
    'middleware' => ['api.key', 'rate.limit:60,1', 'track.call'],
], function ($router) {
    $router->post('/validate', 'NationalIdController@validateId');
    $router->get('/validate', 'NationalIdController@validateId');
});
