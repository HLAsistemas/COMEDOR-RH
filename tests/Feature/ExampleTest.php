<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_guest_home_redirects_to_operator_login(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));
    }
}
