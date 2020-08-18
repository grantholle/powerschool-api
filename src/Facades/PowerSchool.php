<?php

namespace GrantHolle\PowerSchool\Api\Facades;

use Illuminate\Support\Facades\Facade;
use GrantHolle\PowerSchool\Api\RequestBuilder;

class PowerSchool extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return RequestBuilder::class;
    }
}
