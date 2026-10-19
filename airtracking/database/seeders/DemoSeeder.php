<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Services\MeasurementIngestor;
use Illuminate\Database\Seeder;

/** Data contoh untuk mencoba dashboard: php artisan db:seed --class=DemoSeeder (credential: demo-credential-01 sampai -04) */
class DemoSeeder extends Seeder
{
    public function run(MeasurementIngestor $ingestor): void
    {
        // Titik contoh di sekitar Kota Malang (perkiraan). Ubah lewat admin > Titik lokasi.
        $demo = [
            ['AIR-01', 'Kolam Utama', 'LOK-01', 0, -7.978400, 112.634100],
            ['AIR-02', 'Embung Sampel', 'LOK-02', 1, -7.922000, 112.599000],
            ['AIR-03', 'Sungai Brantas Jodipan', 'LOK-03', 2, -7.984300, 112.639500],
            ['AIR-04', 'Selokan Kampus', 'LOK-04', 0, -7.952800, 112.614000],
        ];

        foreach ($demo as $i => [$code, $name, $loc, $shift, $lat, $lng]) {
            $d = Device::firstOrCreate(['code' => $code], ['name' => $name, 'location_code' => $loc]);
            if ($d->latitude === null) {
                $d->update(['latitude' => $lat, 'longitude' => $lng]);
            }
            $d->setCredential('demo-credential-0'.($i + 1));
            if ($d->measurements()->exists()) {
                continue;   // aman dijalankan ulang: riwayat tidak digandakan
            }

            mt_srand(2026 + $i);
            $recs = [];
            for ($n = 0; $n < 90; $n++) {
                $ev = ($n >= 36 && $n <= 44) ? 1 : 0;
                $day = sin($n / 3 * M_PI * 2 / 3);
                $ec = 520 + $shift * 90 + 30 * $day + (mt_rand() / mt_getrandmax() - .5) * 40 + $ev * 170;
                $recs[] = [
                    'ts' => now()->subHours((89 - $n) * 8)->subMinutes(20)->toIso8601ZuluString(),
                    'ph' => round(7.35 + .12 * $day + (mt_rand() / mt_getrandmax() - .5) * .14 - $ev * .45, 2),
                    'ec' => round($ec), 'tds' => round($ec / 2),
                    'do' => round(7.1 - .5 * $day + (mt_rand() / mt_getrandmax() - .5) * .3 - $ev * 1.9, 2),
                    'temp' => round(27.4 + 1.4 * $day, 1),
                    'turb' => round(3 + $shift + (mt_rand() / mt_getrandmax() - .5) * 2 + $ev * 7, 1),
                ];
            }
            $ingestor->ingest($d, ['loc' => $loc, 'records' => $recs, 'bat' => 78 - $i * 11, 'fw' => '0.3.1',
                'cal_ph' => now()->subDays(8)->toDateString(), 'cal_ec' => now()->subDays(8)->toDateString(), 'sd_pending' => max(0, 6 - 6 * $i)], 'sd');
        }
    }
}
