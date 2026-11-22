<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomerAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public static function customerLoginFields(): array
    {
        return [
            'email' => ['email'],
            'nomor HP' => ['no_hp'],
            'username' => ['username'],
        ];
    }

    public function test_tamu_diarahkan_ke_login_customer_saat_membuka_beranda_customer(): void
    {
        $response = $this->get('/customer/beranda');

        $response->assertRedirect(route('customer.login'));
    }

    public function test_halaman_login_customer_menampilkan_satu_field_login(): void
    {
        $response = $this->get('/customer/login');

        $response->assertOk()
            ->assertSee('name="login"', false)
            ->assertSee('name="password"', false);
    }

    #[DataProvider('customerLoginFields')]
    public function test_customer_dapat_login_menggunakan_email_nomor_hp_atau_username(string $field): void
    {
        $customer = User::factory()->create([
            'email' => 'customer@example.com',
            'no_hp' => '081234567890',
            'username' => 'customer01',
        ]);

        $response = $this->from('/customer/login')->post('/customer/login', [
            'login' => $customer->{$field},
            'password' => 'password',
        ]);

        $response->assertRedirect(route('customer.beranda'));
        $this->assertAuthenticatedAs($customer);
    }

    public function test_admin_yang_login_lewat_halaman_customer_diarahkan_ke_dashboard_admin(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'no_hp' => '081111111111',
            'username' => 'admin01',
        ]);

        $response = $this->from('/customer/login')->post('/customer/login', [
            'login' => 'admin01',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);
        $this->get('/customer/beranda')->assertForbidden();
    }

    public function test_admin_tidak_dapat_mengakses_halaman_customer(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/customer/beranda');

        $response->assertForbidden();
    }

    public function test_customer_tidak_dapat_mengakses_halaman_admin(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->get('/admin/dashboard');

        $response->assertForbidden();
    }

    public function test_customer_logout_diarahkan_ke_login_customer(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->post('/logout');

        $response->assertRedirect(route('customer.login'));
        $this->assertGuest();
    }

    public function test_registrasi_customer_menyimpan_nomor_hp_dan_role_user(): void
    {
        $response = $this->post('/customer/daftar-akun', [
            'name' => 'Customer Baru',
            'email' => 'baru@example.com',
            'no_hp' => '081234567891',
            'password' => 'S3cret!Passw0rd',
            'password_confirmation' => 'S3cret!Passw0rd',
        ]);

        $response->assertRedirect(route('customer.beranda'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'baru@example.com',
            'no_hp' => '081234567891',
            'role' => 'user',
        ]);
    }

    public function test_registrasi_menolak_nomor_hp_yang_sudah_dipakai(): void
    {
        User::factory()->create(['no_hp' => '081234567892']);

        $response = $this->from('/customer/daftar-akun')->post('/customer/daftar-akun', [
            'name' => 'Customer Duplikat',
            'email' => 'duplikat@example.com',
            'no_hp' => '081234567892',
            'password' => 'S3cret!Passw0rd',
            'password_confirmation' => 'S3cret!Passw0rd',
        ]);

        $response->assertRedirect('/customer/daftar-akun')
            ->assertSessionHasErrors('no_hp');
        $this->assertGuest();
    }

    public function test_login_customer_dibatasi_setelah_lima_kali_gagal(): void
    {
        $throttleKey = 'throttle@example.com|127.0.0.1';
        RateLimiter::clear($throttleKey);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from('/customer/login')->post('/customer/login', [
                'login' => 'throttle@example.com',
                'password' => 'salahpassword',
            ]);
        }

        $response = $this->from('/customer/login')->post('/customer/login', [
            'login' => 'throttle@example.com',
            'password' => 'salahpassword',
        ]);

        $response->assertRedirect('/customer/login')
            ->assertSessionHasErrors('login');
        $this->assertStringContainsString(
            'Terlalu banyak percobaan login',
            session('errors')->first('login'),
        );
        $this->assertGuest();
    }
}
