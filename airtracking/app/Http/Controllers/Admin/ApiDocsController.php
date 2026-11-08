<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

// Halaman dokumentasi API untuk admin. Isinya statis (tidak menyimpan apa pun);
// contoh curl memakai placeholder, bukan credential sungguhan.
class ApiDocsController extends Controller
{
    public function index(): View
    {
        $base = rtrim(config('app.url') ?: url('/'), '/');

        $public = [
            ['GET', '/api/public/limits', 'Rentang normal tiap parameter (baku mutu).', '60/menit/IP'],
            ['GET', '/api/public/devices', 'Semua alat aktif + bacaan terakhir + status per parameter.', '60/menit/IP'],
            ['GET', '/api/public/devices/{id}', 'Satu alat beserta bacaan terakhir.', '60/menit/IP'],
            ['GET', '/api/public/devices/{id}/history?days=7&limit=500', 'Riwayat pengukuran (days 1–365, limit ≤ 1000).', '60/menit/IP'],
            ['GET', '/api/public/devices/{id}/history.csv?days=30', 'Unduh riwayat sebagai CSV.', '60/menit/IP'],
            ['GET', '/api/public/devices/{id}/predict', 'Prediksi kualitas air terbaru (skor aturan).', '60/menit/IP'],
        ];

        $ingestFields = [
            ['id', 'string', 'wajib', 'ID alat yang sudah diregistrasi (mis. AIR-01).'],
            ['key', 'string', 'wajib', 'Credential alat (hanya tampil sekali di panel admin).'],
            ['loc', 'string', 'opsional', 'Kode lokasi alat.'],
            ['fw', 'string', 'opsional', 'Versi firmware alat.'],
            ['bat', 'integer', 'opsional', 'Persentase baterai 0–100.'],
            ['cal_ph / cal_ec', 'string', 'opsional', 'Tanggal kalibrasi terakhir (YYYY-MM-DD).'],
            ['sd_pending', 'integer', 'opsional', 'Jumlah data yang masih mengantre di microSD.'],
            ['ts', 'string', 'opsional', 'Timestamp UTC ISO-8601. Nilai < 2025-01-01 ditolak; > sekarang+1 hari dikoreksi ke waktu server dan ditandai ts_corrected.'],
            ['ph, ec, tds, do, temp, turb', 'number|null', 'opsional', 'Nilai sensor; minimal satu harus terisi untuk dianggap pengukuran.'],
            ['records[]', 'array', 'opsional', 'Banyak pengukuran sekaligus (maks 500); tiap item berisi ts + nilai sensor.'],
        ];

        $curl = <<<CURL
        curl -X POST {$base}/api/ingest \\
          -H "Content-Type: application/json" \\
          -d '{"id":"AIR-01","key":"<credential-alat>","loc":"LOK-01","fw":"1.1.0",
               "bat":87,"cal_ph":"2026-10-19","cal_ec":"2026-10-19","sd_pending":0,
               "ts":"2026-10-19T02:30:00Z","ph":7.1,"ec":420,"tds":210,
               "do":null,"temp":27.5,"turb":3.2}'
        CURL;

        $sample = json_encode([
            'id' => 'AIR-01',
            'name' => 'Kolam Utama',
            'loc_code' => 'LOK-01',
            'last' => [
                'ts' => '2026-10-17T08:30:00Z',
                'ph' => 7.1, 'ec' => 420, 'tds' => 210, 'do' => 6.8, 'temp' => 27.5, 'turb' => 3.2,
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $send = json_encode([
            'id' => 'AIR-01',
            'key' => '<credential-alat>',
            'loc' => 'LOK-01',
            'fw' => '1.1.0',
            'bat' => 87,
            'cal_ph' => '2026-10-19',
            'cal_ec' => '2026-10-19',
            'sd_pending' => 0,
            'ts' => '2026-10-19T02:30:00Z',
            'ph' => 7.1, 'ec' => 420, 'tds' => 210, 'do' => 6.8, 'temp' => 27.5, 'turb' => 3.2,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return view('admin.api', compact('base', 'public', 'ingestFields', 'curl', 'sample', 'send'));
    }

    // Unduh spesifikasi OpenAPI (dipakai juga oleh alat integrasi/Postman).
    public function openapi(): BinaryFileResponse
    {
        $path = base_path('docs/openapi.yaml');
        abort_unless(is_file($path), 404, 'Berkas OpenAPI belum tersedia.');

        return response()->download($path, 'airtracking-openapi.yaml', ['Content-Type' => 'application/yaml']);
    }

    // Ringkasan JSON untuk kebutuhan otomasi (opsional, dipakai tes).
    public function summary(): JsonResponse
    {
        return response()->json([
            'ingest' => '/api/ingest',
            'public' => [
                '/api/public/limits',
                '/api/public/devices',
                '/api/public/devices/{id}',
                '/api/public/devices/{id}/history',
                '/api/public/devices/{id}/history.csv',
                '/api/public/devices/{id}/predict',
            ],
            'openapi' => route('admin.api.openapi'),
        ]);
    }
}
