<?php

namespace MrNewport\LaravelPlaid\Tests;

use MrNewport\LaravelPlaid\PlaidServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            PlaidServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Plaid' => \MrNewport\LaravelPlaid\Facades\Plaid::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('plaid.client_id', 'test_client_id');
        $app['config']->set('plaid.secret', 'test_secret');
        $app['config']->set('plaid.environment', 'sandbox');
        $app['config']->set('plaid.version', '2020-09-14');
    }
}