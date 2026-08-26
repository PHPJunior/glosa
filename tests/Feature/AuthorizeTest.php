<?php

namespace PhpJunior\Glosa\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use PhpJunior\Glosa\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuthorizeTest extends TestCase
{
    #[Test]
    public function it_allows_access_in_local_environment()
    {
        $this->app['env'] = 'local';

        $this->get('/glosa')
            ->assertStatus(200);
    }

    #[Test]
    public function it_denies_access_in_production_if_gate_is_undefined()
    {
        $this->app['env'] = 'production';

        // Gate 'viewGlosa' is not defined by default

        $this->get('/glosa')
            ->assertStatus(403);
    }

    #[Test]
    public function it_denies_access_in_production_if_gate_returns_false()
    {
        $this->app['env'] = 'production';

        Gate::define('viewGlosa', function ($user = null) {
            return false;
        });

        $this->get('/glosa')
            ->assertStatus(403);
    }

    #[Test]
    public function it_allows_access_in_production_if_gate_returns_true()
    {
        $this->app['env'] = 'production';

        Gate::define('viewGlosa', function ($user = null) {
            return true;
        });

        $this->get('/glosa')
            ->assertStatus(200);
    }
}
