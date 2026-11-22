<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_membuat_satu_admin_dengan_password_terhash(): void
    {
        $this->seed(AdminSeeder::class);

        $admin = User::where('email', 'octaimub@gmail.com')->firstOrFail();

        $this->assertSame('octavia', $admin->name);
        $this->assertSame('admin', $admin->role);
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue(Hash::check('ketuakita', $admin->password));
        $this->assertSame(1, User::count());
    }

    public function test_halaman_login_menampilkan_form_username(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk()
            ->assertSee('Login Admin')
            ->assertSee('name="login"', false)
            ->assertSee('name="password"', false)
            ->assertSee(route('login'), false);
    }

    public function test_kredensial_admin_berhasil_login_dan_diarahkan_ke_dashboard(): void
    {
        $this->seed(AdminSeeder::class);

        $response = $this->post('/admin/login', [
            'login' => 'octaimub@gmail.com',
            'password' => 'ketuakita',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs(User::where('email', 'octaimub@gmail.com')->firstOrFail());
    }

    public function test_login_menggunakan_username_juga_berhasil(): void
    {
        $this->seed(AdminSeeder::class);

        $response = $this->post('/admin/login', [
            'login' => 'octavia',
            'password' => 'ketuakita',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs(User::where('email', 'octaimub@gmail.com')->firstOrFail());
    }

    public function test_kredensial_salah_menampilkan_error_pada_field_username(): void
    {
        $this->seed(AdminSeeder::class);

        $response = $this->from('/admin/login')->post('/admin/login', [
            'login' => 'octaimub@gmail.com',
            'password' => 'salahpassword',
        ]);

        $response->assertRedirect('/admin/login')
            ->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_pengguna_belum_login_diarahkan_ke_halaman_login(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_pengguna_non_admin_tidak_dapat_mengakses_halaman_admin(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertForbidden();
    }

    public function test_admin_dapat_mengakses_halaman_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk()
            ->assertSee('Beranda');
    }

    public function test_halaman_admin_menampilkan_konfirmasi_logout(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk()
            ->assertSee(route('logout'), false)
            ->assertSee('form-konfirmasi', false)
            ->assertSee('Yakin ingin keluar dari akun ini?', false);
    }

    public function test_logout_mengarahkan_kembali_ke_halaman_login(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
