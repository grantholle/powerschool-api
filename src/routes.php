<?php

/*
|--------------------------------------------------------------------------
| Plugin Registration
|--------------------------------------------------------------------------
|
| This defines the endpoint and action taken when the plugin gets installed.
| When the plugin is installed, a request is send to the endpoint to
| register the client ID and secret (and anything else we want).
| Here we'll get the client ID and secret, cache the access_token
| and send back a successful response.
|
 */

Route::post('/powerschool/registration', 'GrantHolle\PowerSchool\Controllers\RegisterController@register');
