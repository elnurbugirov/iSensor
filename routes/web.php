<?php

use App\User;
use Illuminate\Support\Facades\Auth;

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->post('otp', 'AuthController@otpCreate');
    $router->post('otpValidate', 'AuthController@otpValidate');
    $router->put('refresh_token', 'AuthController@refreshToken');
    $router->get('logout', function () {
        Auth::logout();
    });
    $router->get('countries', 'Controller@getCountries');
    $router->get('clientNotify', 'Controller@warning');
    $router->get('checkSms', 'Controller@checkSmsDelivery');

    $router->group(['prefix' => 'devices'], function () use ($router) {
       $router->get('/', 'DeviceController@index');
       $router->post('/', 'DeviceController@store');
       $router->delete('/{id}', 'DeviceController@delete');
       $router->post('/{id}', 'DeviceController@setRange');
    });

    $router->group(['prefix' => 'profile'], function () use ($router) {
        $router->get('/', function () {
            return User::find(Auth::id());
        });
        $router->post('/', 'UserController@update');
    });
});
