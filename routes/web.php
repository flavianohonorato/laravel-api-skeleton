<?php

Route::get('/', function () {
    return view('welcome');
});

Route::get('user/verify/{verification_code}', 'AuthController@verifyUser');
