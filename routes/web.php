<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return 'not_allowed';
});

$router->get('/api/forexcurrency/{api_token}', 'DownloadCurrencyController@GetMajorForexCurrency');

$router->get('/api/forexcurrency', function() use ($router){
    return response()->json(['status' => false, 'message' => 'Missing Token']);
});

$router->get('/api', function() use ($router){
    return response()->json(['status' => false, 'message' => 'Missing Token']);
});
