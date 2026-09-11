<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_mobile_login_returns_token(): void
    {
        $this->seed();

        $response = $this->postJson('/api/mobile/auth/superadmin/login', [
            'email' => 'admin@companysuite.local',
            'password' => 'admin123',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'token',
            'user' => ['id', 'email', 'role'],
        ]);
    }
}
