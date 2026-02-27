<?php

namespace DevRavik\LaravelLicensing\Facades;

use Illuminate\Support\Facades\Facade;

class License extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'license';
    }
}
