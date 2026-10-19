<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\User;
use Database\Seeders\ParameterLimitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LokasiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ParameterLimitSeeder::class);
    }

    private function device(array $extra = []): Device
    {
        return Device::create(['code' => 'AIR-01', 'name' => 'Kolam', 'location_code' => 'LOK-01'] + $extra);
    }

    public function test_peta_publik_terbuka_tanpa_login(): void
    {
        $this->get('/lokasi')->assertOk()->assertSee('Peta lokasi');
    }

    public function test_api_publik_menyertakan_koordinat_atau_null(): void
    {
        $this->device(['latitude' => -7.9784, 'longitude' => 112.6341]);
        Device::create(['code' => 'AIR-02', 'name' => 'Tanpa titik', 'location_code' => 'LOK-02']);

        $this->getJson('/api/public/devices')->assertOk()
            ->assertJsonPath('0.lat', -7.9784)->assertJsonPath('0.lng', 112.6341)
            ->assertJsonPath('1.lat', null)->assertJsonPath('1.lng', null);
    }

    public function test_halaman_admin_titik_lokasi_butuh_login(): void
    {
        $this->get('/admin/lokasi')->assertRedirect('/admin/login');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin/lokasi')->assertOk();
    }

    public function test_admin_menyimpan_titik_alat(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $d = $this->device();

        $this->actingAs($admin)->put("/admin/devices/{$d->id}/lokasi", ['latitude' => '-7.978400', 'longitude' => '112.634100'])
            ->assertRedirect('/admin/lokasi?alat=AIR-01');

        $d->refresh();
        $this->assertEquals(-7.9784, $d->latitude);
        $this->assertEquals(112.6341, $d->longitude);
    }

    public function test_koordinat_tidak_valid_ditolak_dan_akun_biasa_tidak_bisa_menyimpan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $d = $this->device();

        $this->actingAs($admin)->put("/admin/devices/{$d->id}/lokasi", ['latitude' => 95, 'longitude' => 112.6])->assertSessionHasErrors('latitude');
        $this->actingAs($admin)->put("/admin/devices/{$d->id}/lokasi", ['latitude' => -7.9])->assertSessionHasErrors('longitude');
        $this->assertNull($d->refresh()->latitude);

        $this->actingAs(User::factory()->create())->put("/admin/devices/{$d->id}/lokasi", ['latitude' => -7.9, 'longitude' => 112.6])
            ->assertRedirect('/admin/login');
        $this->assertNull($d->refresh()->latitude);
    }

    public function test_registrasi_alat_boleh_sekaligus_dengan_koordinat(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/devices', [
            'code' => 'AIR-07', 'name' => 'Uji', 'location_code' => 'LOK-07', 'latitude' => -7.95, 'longitude' => 112.61,
        ])->assertRedirect('/admin/devices');

        $this->assertEquals(-7.95, Device::where('code', 'AIR-07')->first()->latitude);
    }
}
