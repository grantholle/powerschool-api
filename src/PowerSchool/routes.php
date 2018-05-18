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
Route::post('/powerschool-registration', function () {
    // {
    //     "credentials" : {
    //         "client_secret" :"3260cf45-41c3-4f16-b8e3-8b120a4afc54" ,
    //             "client_id" : "66fc77ee-359b-4f15-971e-8bd5d3e83fd7"
    //     },
    //         "verify_url" :"https://applegrove.com/ws/v1/time" ,
    //         "callback_data" : "License Key"
    // }
});
