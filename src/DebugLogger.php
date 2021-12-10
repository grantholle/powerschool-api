<?php

namespace GrantHolle\PowerSchool\Api;

class DebugLogger
{
    public static function log($data)
    {
        if (
            config('app.debug') &&
            config('powerschool.debug_with_ray') &&
            function_exists('ray')
        ) {
            if (is_callable($data)) {
                $data();
                return;
            }

            ray($data)->purple();
        }
    }
}
