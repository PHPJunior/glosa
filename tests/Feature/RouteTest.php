<?php

namespace PhpJunior\Glosa\Tests\Feature;

use PhpJunior\Glosa\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RouteTest extends TestCase
{
    #[Test]
    public function it_can_access_the_translation_index_page()
    {
        $response = $this->get('/glosa');

        $response->assertStatus(200);
        $response->assertSee('Glosa');
        $response->assertSee('TMS');
    }
}
