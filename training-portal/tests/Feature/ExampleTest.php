<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_homepage_requires_admin_authentication(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
