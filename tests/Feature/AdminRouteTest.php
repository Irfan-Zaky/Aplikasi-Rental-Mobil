<?php

namespace Tests\Feature;

use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_ke_login_admin(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_customer_tidak_dapat_membuka_halaman_admin(): void
    {
        $this->actingAs(Account::factory()->create())
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_diarahkan_ke_dashboard_admin(): void
    {
        $this->actingAs(Account::factory()->admin()->create())
            ->get('/admin')
            ->assertRedirect(route('admin.dashboard'));
    }
}
