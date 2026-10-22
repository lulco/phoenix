<?php

declare(strict_types=1);

namespace Phoenix\Tests\Mock\Database\Adapter;

use Phoenix\Config\EnvironmentConfig;
use Phoenix\Database\Adapter\AdapterFactoryInterface;
use Phoenix\Database\Adapter\AdapterInterface;

final class MockAdapterFactory implements AdapterFactoryInterface
{
    public static function instance(EnvironmentConfig $config): AdapterInterface
    {
        throw new \RuntimeException('Mock adapter factory should not be instantiated in tests');
    }
}
