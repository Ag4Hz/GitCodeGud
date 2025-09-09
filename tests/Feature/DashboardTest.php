<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_everyone_can_view_the_dashboard()
    {
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }
}
