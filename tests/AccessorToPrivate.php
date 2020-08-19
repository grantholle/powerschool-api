<?php

namespace Tests;

use Closure;

class AccessorToPrivate
{
    public function get($obj, $attribute) {
        $getter = function() use ($attribute) {
            return $this->$attribute;
        };

        return Closure::bind($getter, $obj, get_class($obj));
    }

    public function set($obj, $attribute) {
        $setter = function($value) use ($attribute) {
            $this->$attribute = $value;
        };

        return Closure::bind($setter, $obj, get_class($obj));
    }
}
