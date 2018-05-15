<?php

namespace PowerSchool;

use Illuminate\Support\Facades\Facade;
use PowerSchool\Api\RequestBuilder;

class PowerSchool extends Facade
{
    protected static function getFacadeAccessor()
    {
        return RequestBuilder::class;
    }
}
