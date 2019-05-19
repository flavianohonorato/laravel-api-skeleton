<?php

Route::post('register', 'AuthController@register');
Route::post('login', 'AuthController@login');
Route::post('recover', 'AuthController@recover');
Route::get('refresh', 'AuthController@refresh');

Route::group(['middleware' => ['jwt.auth']], function() {
    Route::get('user', 'AuthController@user');
    Route::get('logout', 'AuthController@logout');
});
