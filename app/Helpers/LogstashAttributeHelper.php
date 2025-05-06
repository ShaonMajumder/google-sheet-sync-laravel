<?php

namespace App\Helpers;

use Illuminate\Http\Request;

class LogstashAttributeHelper
{
    /**
     * Set multiple attributes on the request object.
     *
     * @param Request $request
     * @param array $attributes
     * @return void
     */
    public static function setAttributes(Request $request, array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $request->attributes->set($key, $value);
        }
    }
}
