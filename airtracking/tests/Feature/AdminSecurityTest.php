<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** P0-05: hardening login (batas per IP) dan credential alat (minimal 20 karakter) + audit log. */
class AdminSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_credential_manual_kurang_dari_20_karakter_ditolak(): void
    {
        $this->actingAs($this->admin())->post('/admin/devices', [
            'code' => 'AIR-01', 'name' => 'Uji', 'location_code' => 'LOK-01',
            'credential' => 'pendek-sekali',
        ])->assertSessionHasErrors('credential');

        $this->assertDatabaseCount('devices', 0);
    }

    public function test_credential_acak_panjang_dan_langsung_bisa_dipakai_alat(): void
    {
        $this->actingAs($this->admin())->post('/admin/devices', [
            'code' => 'AIR-01', 'name' => 'Uji', 'location_code' => 'LOK-01',
        ])->assertRedirect('/admin/devices')->assertSessionHas('credential');

        $plain = (string) session('credential.key');
        $this->assertGreaterThanOrEqual(20, strlen($plain));

        $this->postJson('/api/ingest', ['id' => 'AIR-01', 'key' => $plain, 'ph' => 7.0])->assertOk();
    }

    public function test_rotasi_credential_membuat_kunci_baru_dan_kunci_lama_gagal(): void
    {
        $device = Device::create(['code' => 'AIR-01', 'name' => 'Uji', 'location_code' => 'LOK-01', 'is_active' => true]);
        $lama = 'credential-lama-yang-cukup-panjang';
        $device->setCredential($lama);

        $this->actingAs($this->admin())->post("/admin/devices/{$device->id}/rotate")
            ->assertRedirect('/admin/devices')->assertSessionHas('credential');

        $baru = (string) session('credential.key');
        $this->assertNotSame($lama, $baru);
        $this->assertGreaterThanOrEqual(20, strlen($baru));

        $this->postJson('/api/ingest', ['id' => 'AIR-01', 'key' => $lama, 'ph' => 7.0])->assertStatus(401);
        $this->postJson('/api/ingest', ['id' => 'AIR-01', 'key' => $baru, 'ph' => 7.0])->assertOk();
    }

    public function test_login_dibatasi_per_ip_walau_email_diganti_ganti(): void
    {
        $admin = $this->admin();

        // 20 kegagalan dari IP yang sama, masing-masing dengan email berbeda.
        for ($i = 1; $i <= 20; $i++) {
            $this->post('/admin/login', ['email' => "acak{$i}@example.test", 'password' => 'salah'])
                ->assertSessionHasErrors('email');
        }

        // Percobaan ke-21 dengan kredensial benar tetap diblokir.
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_dan_aksi_admin_tercatat_di_audit_log_tanpa_credential(): void
    {
        $admin = $this->admin();

        $this->post('/admin/login', ['email' => 'salah@example.test', 'password' => 'salah']);
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect('/admin/devices');

        $this->post('/admin/devices', ['code' => 'AIR-01', 'name' => 'Uji', 'location_code' => 'LOK-01'])
            ->assertSessionHas('credential');
        $plain = (string) session('credential.key');

        $this->assertDatabaseHas('audit_logs', ['action' => 'login.gagal', 'user_id' => null]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'login.berhasil', 'user_id' => $admin->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'device.buat', 'subject_id' => Device::first()->id]);

        $log = AuditLog::where('action', 'device.buat')->firstOrFail();
        $this->assertSame(['code' => 'AIR-01'], $log->meta);
        $this->assertStringNotContainsString($plain, (string) json_encode($log->meta));
    }

    public function test_logout_tercatat(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/logout')->assertRedirect('/');

        $this->assertDatabaseHas('audit_logs', ['action' => 'logout', 'user_id' => $admin->id]);
    }

    public function test_halaman_audit_hanya_untuk_admin_dan_menampilkan_catatan(): void
    {
        $this->get('/admin/audit')->assertRedirect('/admin/login');

        AuditLog::create([
            'action' => 'device.buat', 'subject_type' => Device::class, 'subject_id' => 1,
            'ip' => '127.0.0.1', 'meta' => ['code' => 'AIR-01'], 'created_at' => now(),
        ]);

        $this->actingAs($this->admin())->get('/admin/audit')
            ->assertOk()
            ->assertSee('device.buat')
            ->assertSee('AIR-01')
            ->assertSee('127.0.0.1');
    }
}
