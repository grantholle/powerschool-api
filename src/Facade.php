<?php

namespace GrantHolle\PowerSchool;

use Illuminate\Support\Facades\Facade;
use GrantHolle\PowerSchool\Api\RequestBuilder;

class PowerSchool extends Facade
{
    protected static function getFacadeAccessor()
    {
        return RequestBuilder::class;
    }
}
