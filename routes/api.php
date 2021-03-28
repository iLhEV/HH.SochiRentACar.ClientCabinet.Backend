<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


//Роуты для личного кабинета клиента
Route::prefix('auth-client')->group(function () {
    Route::post('login', 'Api\ClientAccount\ClientAuthController@login');
    Route::post('register', 'Api\ClientAccount\ClientAuthController@register');
});
Route::middleware('auth-client:api-client')->group(function () {
    Route::prefix('auth-client')->group(function () {
        Route::get('client', 'Api\ClientAccount\ClientAuthController@getClient');
        Route::get('logout', 'Api\ClientAccount\ClientAuthController@logout');
    });
    Route::resource('clients', 'Api\ClientAccount\ClientsController');
    Route::resource('rents', 'Api\ClientAccount\RentsController');
    Route::get('/bonusmiles/{id}/charge', 'Api\ClientAccount\BonusMilesController@charge');
    //Водители
    Route::get('drivers/', 'Api\ClientAccount\DriversController@index');
    Route::get('drivers/{id}', 'Api\ClientAccount\DriversController@show');
    Route::post('drivers/create', 'Api\ClientAccount\DriversController@create');
    Route::delete('drivers/delete', 'Api\ClientAccount\DriversController@delete');
    Route::put('drivers/{id}', 'Api\ClientAccount\DriversController@update');
});

