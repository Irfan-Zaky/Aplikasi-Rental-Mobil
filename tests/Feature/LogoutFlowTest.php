<?php

namespace Tests\Feature;

use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_logout_mengakhiri_sesi_dan_kembali_ke_login_customer(): void
    {
        $customer = Account::factory()->create();

        $response = $this->actingAs($customer)->post(route('logout'));

        $response->assertRedirect(route('customer.login'));
        $this->assertGuest();
    }

    public function test_admin_logout_mengakhiri_sesi_dan_kembali_ke_login_admin(): void
    {
        $admin = Account::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_halaman_profil_customer_memiliki_tombol_logout(): void
    {
        $customer = Account::factory()->create();

        $this->actingAs($customer)->get(route('customer.profil'))
            ->assertOk()
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('Logout');
    }

    public function test_sidebar_admin_memiliki_tombol_logout(): void
    {
        $admin = Account::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('Log Out');
    }
}
