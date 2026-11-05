<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /** Ang "/" ay nagre-redirect sa dashboard (at ang guest ay sa login). */
    public function test_home_redirects_to_dashboard(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
