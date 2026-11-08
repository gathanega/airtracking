<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Measurement;
use Database\Seeders\ParameterLimitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** P0-04: integritas data masuk — heartbeat, record kosong, dan validasi timestamp. */
class IngestIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'rahasia-alat-1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ParameterLimitSeeder::class);
    }

    private function device(): Device
    {
        $d = Device::create(['code' => 'AIR-01', 'name' => 'Kolam', 'location_code' => 'LOK-01', 'is_active' => true]);
        $d->setCredential(self::KEY);

        return $d;
    }

    /** Payload persis seperti firmware v1.1.0. */
    private function payload(array $extra = []): array
    {
        return array_merge([
            'id' => 'AIR-01', 'key' => self::KEY, 'loc' => 'LOK-01', 'fw' => '1.1.0',
            'bat' => 87, 'cal_ph' => '2026-10-19', 'cal_ec' => '2026-10-19', 'sd_pending' => 0,
            'ts' => now()->subMinutes(5)->toIso8601ZuluString(),
            'ph' => 7.1, 'ec' => 420, 'tds' => 210, 'do' => 6.8, 'temp' => 27.5, 'turb' => 3.2,
        ], $extra);
    }

    public function test_payload_lengkap_firmware_v110_tetap_diterima(): void
    {
        $d = $this->device();

        $this->postJson('/api/ingest', $this->payload())
            ->assertOk()
            ->assertJson([
                'ok' => true, 'saved' => 1, 'skipped' => 0,
                'ts_rejected' => 0, 'ts_corrected' => 0, 'heartbeat' => false,
            ]);

        $this->assertDatabaseCount('measurements', 1);
        $this->assertFalse(Measurement::first()->ts_corrected);
        $this->assertEquals(87, $d->refresh()->battery_pct);
    }

    public function test_records_kosong_tidak_membuat_baris_dan_ditolak(): void
    {
        $this->device();

        $this->postJson('/api/ingest', [
            'id' => 'AIR-01', 'key' => self::KEY, 'loc' => 'LOK-01',
            'ts' => now()->subMinutes(5)->toIso8601ZuluString(),
            'ph' => 7.1, 'records' => [],
        ])->assertStatus(422)->assertJson(['ok' => false, 'saved' => 0, 'heartbeat' => false]);

        $this->assertDatabaseCount('measurements', 0);
    }

    public function test_record_semua_null_dilewati_tanpa_menggagalkan_record_valid(): void
    {
        $this->device();

        $this->postJson('/api/ingest', [
            'id' => 'AIR-01', 'key' => self::KEY, 'loc' => 'LOK-01',
            'records' => [
                ['ts' => now()->subMinutes(20)->toIso8601ZuluString(),
                    'ph' => null, 'ec' => null, 'tds' => null, 'do' => null, 'temp' => null, 'turb' => null],
                ['ts' => now()->subMinutes(10)->toIso8601ZuluString(), 'ph' => 7.2],
            ],
        ])->assertOk()->assertJson(['ok' => true, 'saved' => 1, 'skipped' => 1]);

        $this->assertDatabaseCount('measurements', 1);
        $this->assertEquals(7.2, Measurement::first()->ph);
    }

    public function test_heartbeat_hanya_memperbarui_status_tanpa_baris_pengukuran(): void
    {
        $d = $this->device();
        $this->postJson('/api/ingest', $this->payload(['ph' => 7.4]))->assertOk();
        $sebelum = $d->refresh()->last_reading;

        // Tanpa nilai sensor, hanya field status: harus dianggap heartbeat.
        $this->postJson('/api/ingest', [
            'id' => 'AIR-01', 'key' => self::KEY, 'loc' => 'LOK-01', 'fw' => '1.1.0',
            'bat' => 42, 'cal_ph' => '2026-10-19', 'cal_ec' => '2026-10-19', 'sd_pending' => 3,
        ])->assertOk()->assertJson([
            'ok' => true, 'saved' => 0, 'skipped' => 1, 'heartbeat' => true,
        ]);

        $d->refresh();
        $this->assertDatabaseCount('measurements', 1);
        $this->assertEquals(42, $d->battery_pct);
        $this->assertEquals(3, $d->sd_pending);
        $this->assertNotNull($d->last_seen_at);
        $this->assertEquals($sebelum, $d->last_reading);
    }

    public function test_payload_tanpa_sensor_dan_tanpa_status_ditolak_422(): void
    {
        $this->device();

        $this->postJson('/api/ingest', ['id' => 'AIR-01', 'key' => self::KEY, 'loc' => 'LOK-01'])
            ->assertStatus(422)
            ->assertJson(['ok' => false, 'saved' => 0, 'heartbeat' => false]);

        $this->assertDatabaseCount('measurements', 0);
    }

    public function test_timestamp_1970_ditolak(): void
    {
        $this->device();

        $this->postJson('/api/ingest', [
            'id' => 'AIR-01', 'key' => self::KEY, 'loc' => 'LOK-01',
            'ts' => '1970-01-01T00:00:00Z', 'ph' => 7.1,
        ])
            ->assertStatus(422)
            ->assertJson(['ok' => false, 'saved' => 0, 'ts_rejected' => 1, 'heartbeat' => false]);

        $this->assertDatabaseCount('measurements', 0);
    }

    public function test_timestamp_masa_depan_dikoreksi_dan_ditandai(): void
    {
        $this->device();

        $this->postJson('/api/ingest', $this->payload(['ts' => now()->addDays(10)->toIso8601ZuluString()]))
            ->assertOk()
            ->assertJson(['ok' => true, 'saved' => 1, 'ts_corrected' => 1]);

        $m = Measurement::first();
        $this->assertTrue($m->ts_corrected);
        $this->assertTrue($m->measured_at->between(now()->subMinute(), now()->addMinute()));
    }

    public function test_record_dengan_timestamp_lama_dilewati_sisanya_tersimpan(): void
    {
        $this->device();

        $this->postJson('/api/ingest', [
            'id' => 'AIR-01', 'key' => self::KEY, 'loc' => 'LOK-01',
            'records' => [
                ['ts' => '1970-01-01T00:00:00Z', 'ph' => 6.5],
                ['ts' => now()->subMinutes(10)->toIso8601ZuluString(), 'ph' => 7.3],
            ],
        ])->assertOk()->assertJson(['ok' => true, 'saved' => 1, 'ts_rejected' => 1]);

        $this->assertDatabaseCount('measurements', 1);
        $this->assertEquals(7.3, Measurement::first()->ph);
    }
}
