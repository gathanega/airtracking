<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApiDocsTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_api_butuh_login_admin(): void
    {
        $this->get('/admin/api')->assertRedirect('/admin/login');

        $biasa = User::factory()->create(['role' => 'user']);
        $this->actingAs($biasa)->get('/admin/api')->assertRedirect('/admin/login');
    }

    public function test_admin_melihat_endpoint_dan_contoh_curl(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/api')
            ->assertOk()
            ->assertSee('/api/ingest')
            ->assertSee('/api/public/devices')
            ->assertSee('/api/public/limits')
            ->assertSee('curl -X POST')
            ->assertSee('Unduh OpenAPI')
            ->assertSee('Contoh respons satu alat')
            ->assertSee('Kirim data dari alat');
    }

    public function test_openapi_bisa_diunduh_admin_dan_berisi_ingest(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/api/openapi.yaml')
            ->assertOk()
            ->assertDownload('airtracking-openapi.yaml');

        $this->assertStringContainsString('/api/ingest', file_get_contents(base_path('docs/openapi.yaml')));
    }

    public function test_ringkasan_json_menyebut_endpoint_utama(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->getJson('/admin/api/summary.json')
            ->assertOk()
            ->assertJsonPath('ingest', '/api/ingest')
            ->assertJsonFragment(['/api/public/devices']);
    }
}
