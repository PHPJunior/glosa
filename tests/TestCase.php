<?php

namespace PhpJunior\Glosa\Tests;

use PhpJunior\Glosa\GlosaServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            GlosaServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Ensure config is loaded if not published in test env automatically
        $app['config']->set('glosa', require __DIR__ . '/../config/glosa.php');
    }
}
