<?php

namespace PhpJunior\Glosa\Tests\Feature;

use PhpJunior\Glosa\Tests\TestCase;

class RouteTest extends TestCase
{
    /** @test */
    public function it_can_access_the_translation_index_page()
    {
        $response = $this->get('/glosa');

        $response->assertStatus(200);
        $response->assertSee('Glosa');
        $response->assertSee('TMS');
    }
}
