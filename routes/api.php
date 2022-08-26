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

Route::prefix('auth')->group(function () {
    Route::post('register', 'AuthController@register');
    Route::post('login', 'AuthController@login');
    Route::post('login/g2f', 'AuthController@validateG2fLogin');
    Route::get('refresh', 'AuthController@refresh');
    Route::post('logout', 'AuthController@logout');
});

Route::get('/account/activate/{confirmation_code}', 'UserController@activateAccount');
Route::post('/account/send/forgotEmail', 'UserController@sendForgotEmail');
Route::post('/validate/reset/password', 'UserController@validateResetPasswordLinkRequest');
Route::post('/account/reset/password', 'UserController@resetPassword');

Route::group(['middleware' => 'auth:api'], function(){
    Route::post('g2f/enable', 'UserController@enableG2f');
    Route::post('g2f/validate', 'UserController@validateG2fCode');
    Route::post('change/password', 'UserController@changePassword');

    // Users
    Route::get('users', 'UserController@index')->middleware('isAdmin');
    Route::get('users/{id}', 'UserController@show')->middleware('isAdminOrSelf');
});
