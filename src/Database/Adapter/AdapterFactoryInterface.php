<?php

declare(strict_types=1);

namespace Phoenix\Database\Adapter;

use Phoenix\Config\EnvironmentConfig;

interface AdapterFactoryInterface
{
    public static function instance(EnvironmentConfig $config): AdapterInterface;
}
