<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengunjung_melihat_menu_publik_dan_tombol_masuk_admin(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Pemantauan kualitas air')->assertSee('Peta lokasi')->assertSee('Masuk admin')
            ->assertDontSee('Kelola alat');
    }

    public function test_admin_melihat_menu_admin_dan_tombol_keluar_di_halaman_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/devices')->assertOk()
            ->assertSee('Kelola alat')->assertSee('Titik lokasi')->assertSee('Rentang normal')
            ->assertSee('Keluar')->assertSee($admin->email);

        // di halaman publik, admin yang sudah login mendapat pintasan ke panel
        $this->actingAs($admin)->get('/')->assertOk()->assertSee('Panel admin')->assertDontSee('Masuk admin');
    }
}
