<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceCredential;
use App\Models\User;
use Database\Seeders\ParameterLimitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AirTrackingTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'rahasia-alat-1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ParameterLimitSeeder::class);
    }

    private function device(bool $active = true): Device
    {
        $d = Device::create(['code' => 'AIR-01', 'name' => 'Kolam', 'location_code' => 'LOK-01', 'is_active' => $active]);
        $d->setCredential(self::KEY);
        return $d;
    }

    private function payload(array $extra = []): array
    {
        return array_merge(['id' => 'AIR-01', 'key' => self::KEY, 'loc' => 'LOK-01', 'ts' => '2026-10-15T08:30:00Z',
            'ph' => 7.1, 'ec' => 420, 'tds' => 210, 'do' => 6.8, 'temp' => 27.5, 'turb' => 3.2], $extra);
    }

    public function test_ingest_menyimpan_data_dan_memperbarui_bacaan_terakhir(): void
    {
        $d = $this->device();

        $this->postJson('/api/ingest', $this->payload(['bat' => 77, 'sd_pending' => 4]))
            ->assertOk()->assertJson(['ok' => true, 'saved' => 1]);

        $d->refresh();
        $this->assertDatabaseCount('measurements', 1);
        $this->assertDatabaseCount('predictions', 1);
        $this->assertEquals(7.1, $d->last_reading['ph']);
        $this->assertEquals(77, $d->battery_pct);
        $this->assertEquals(4, $d->sd_pending);
        $this->assertNotNull($d->last_seen_at);
    }

    public function test_credential_salah_alat_tidak_dikenal_dan_alat_nonaktif_ditolak(): void
    {
        $this->device();
        $this->postJson('/api/ingest', $this->payload(['key' => 'salah-salah']))->assertStatus(401);
        $this->postJson('/api/ingest', $this->payload(['id' => 'AIR-99']))->assertStatus(401);

        Device::where('code', 'AIR-01')->update(['is_active' => false]);
        $this->postJson('/api/ingest', $this->payload())->assertStatus(401);
        $this->assertDatabaseCount('measurements', 0);
    }

    public function test_nilai_di_luar_batas_fisik_ditolak(): void
    {
        $this->device();
        $this->postJson('/api/ingest', $this->payload(['ph' => 99]))->assertStatus(422);
    }

    public function test_kirim_ulang_data_yang_sama_tidak_menggandakan(): void
    {
        $this->device();
        $this->postJson('/api/ingest', $this->payload())->assertOk();
        $this->postJson('/api/ingest', $this->payload(['ph' => 7.3]))->assertOk();

        $this->assertDatabaseCount('measurements', 1);
        $this->assertEquals(7.3, \App\Models\Measurement::first()->ph);
    }

    public function test_paket_sd_lama_tidak_menimpa_bacaan_terbaru(): void
    {
        $d = $this->device();
        $this->postJson('/api/ingest', $this->payload(['ts' => '2026-10-16T08:00:00Z', 'ph' => 7.5]))->assertOk();

        $batch = ['id' => 'AIR-01', 'key' => self::KEY, 'loc' => 'LOK-01', 'records' => [
            ['ts' => '2026-10-10T08:00:00Z', 'ph' => 6.9], ['ts' => '2026-10-11T08:00:00Z', 'ph' => 7.0],
        ]];
        $this->postJson('/api/ingest', $batch)->assertOk()->assertJson(['saved' => 2]);

        $this->assertDatabaseCount('measurements', 3);
        $this->assertEquals(7.5, $d->refresh()->last_reading['ph']);
    }

    public function test_api_publik_tidak_membocorkan_credential_dan_riwayat_berurutan(): void
    {
        $this->device();
        $this->postJson('/api/ingest', $this->payload(['ts' => now()->subDays(2)->toIso8601ZuluString(), 'ph' => 7.0]))->assertOk();
        $this->postJson('/api/ingest', $this->payload(['ts' => now()->subDay()->toIso8601ZuluString(), 'ph' => 7.2]))->assertOk();

        $list = $this->getJson('/api/public/devices')->assertOk()->assertJsonPath('0.id', 'AIR-01');
        $this->assertStringNotContainsString('key_hash', $list->getContent());
        $this->assertStringNotContainsString(self::KEY, $list->getContent());

        $h = $this->getJson('/api/public/devices/AIR-01/history?days=7')->assertOk()->json('data');
        $this->assertCount(2, $h);
        $this->assertLessThan($h[1]['measured_at'], $h[0]['measured_at']);

        $this->getJson('/api/public/devices/AIR-01/predict')->assertOk()->assertJsonStructure(['label', 'probs', 'basis_ts']);
        $this->getJson('/api/public/limits')->assertOk()->assertJsonPath('ph.lo', 6.5);
        $this->getJson('/api/public/devices/TIDAK-ADA')->assertNotFound();
    }

    public function test_dashboard_publik_terbuka_tanpa_login_dan_admin_butuh_login(): void
    {
        $this->get('/')->assertOk();
        $this->get('/admin/devices')->assertRedirect('/admin/login');
    }

    public function test_akun_biasa_tidak_bisa_masuk_admin_tetapi_admin_bisa(): void
    {
        $user = User::factory()->create();
        $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->get('/admin/devices')->assertRedirect('/admin/login');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect('/admin/devices');
        $this->get('/admin/devices')->assertOk();
    }

    public function test_admin_menambah_alat_credential_disimpan_sebagai_hash_dan_tampil_sekali(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $res = $this->actingAs($admin)->post('/admin/devices', ['code' => 'AIR-09', 'name' => 'Uji', 'location_code' => 'LOK-09'])
            ->assertRedirect('/admin/devices')->assertSessionHas('credential');

        $plain = session('credential.key');
        $row = DeviceCredential::first();
        $this->assertNotSame($plain, $row->key_hash);
        $this->assertSame(hash('sha256', $plain), $row->key_hash);
        $this->assertSame(substr($plain, -4), $row->key_hint);

        // credential baru langsung bisa dipakai alat
        $this->postJson('/api/ingest', ['id' => 'AIR-09', 'key' => $plain, 'ph' => 7.0])->assertOk();
    }

    public function test_admin_mengubah_rentang_normal(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $limits = ['ph' => ['lo' => 6.0, 'hi' => 9.0]];

        $this->actingAs($admin)->put('/admin/limits', ['limits' => $limits])->assertRedirect('/admin/limits');
        $this->getJson('/api/public/limits')->assertJsonPath('ph.lo', 6)->assertJsonPath('ph.hi', 9);

        $this->actingAs($admin)->put('/admin/limits', ['limits' => ['ph' => ['lo' => 9, 'hi' => 6]]])->assertSessionHasErrors('limits');
    }
}
