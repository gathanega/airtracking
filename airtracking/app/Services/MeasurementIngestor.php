<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Measurement;
use App\Models\Prediction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Menyimpan satu atau banyak data dari alat (HTTP, MQTT, atau paket SD card). */
class MeasurementIngestor
{
    /** @param array<string,mixed> $data payload tervalidasi; "records" opsional untuk paket banyak data */
    public function ingest(Device $device, array $data, string $source = 'http'): int
    {
        $now = now()->utc();
        $recs = !empty($data['records']) ? $data['records'] : [$data];

        $rows = [];
        foreach (array_slice($recs, 0, 500) as $r) {
            $ts = !empty($r['ts']) ? Carbon::parse($r['ts'])->utc() : $now->copy();
            if ($ts->gt($now->copy()->addDay())) {
                $ts = $now->copy();                       // jam alat salah (jauh di masa depan)
            }
            $key = $ts->toDateTimeString();
            $row = [
                'device_id' => $device->id,
                'location_code' => $data['loc'] ?? $device->location_code,
                'measured_at' => $key,
                'received_at' => $now->toDateTimeString(),
                'source' => $source,
            ];
            foreach (Quality::COLUMNS as $json => $col) {
                $row[$col] = isset($r[$json]) ? (float) $r[$json] : null;
            }
            $rows[$key] = $row;                           // duplikat waktu dalam satu paket: yang terakhir dipakai
        }
        $rows = array_values($rows);

        DB::transaction(function () use ($device, $data, $rows, $now) {
            $update = array_merge(['source', 'location_code', 'received_at'], array_values(Quality::COLUMNS));
            Measurement::upsert($rows, ['device_id', 'measured_at'], $update);

            usort($rows, fn ($a, $b) => strcmp($a['measured_at'], $b['measured_at']));
            $newest = end($rows);

            $upd = ['last_seen_at' => $now];
            foreach (['fw' => 'firmware_version', 'bat' => 'battery_pct', 'cal_ph' => 'ph_calibrated_on',
                      'cal_ec' => 'ec_calibrated_on', 'sd_pending' => 'sd_pending'] as $in => $col) {
                if (isset($data[$in])) {
                    $upd[$col] = $data[$in];
                }
            }

            // last_reading hanya maju: paket SD lama tidak boleh menimpa bacaan yang lebih baru.
            $prev = $device->last_reading['ts'] ?? null;
            $prevKey = $prev ? Carbon::parse($prev)->utc()->toDateTimeString() : null;
            if ($prevKey === null || $newest['measured_at'] >= $prevKey) {
                $reading = ['ts' => Carbon::parse($newest['measured_at'], 'UTC')->toIso8601ZuluString()];
                foreach (Quality::COLUMNS as $json => $col) {
                    $reading[$json] = $newest[$col];
                }
                $upd['last_reading'] = $reading;

                $p = Quality::predict($reading);
                Prediction::create([
                    'device_id' => $device->id,
                    'measurement_id' => Measurement::where('device_id', $device->id)
                        ->where('measured_at', $newest['measured_at'])->value('id'),
                    'label' => $p['label'],
                    'probabilities' => $p['probs'],
                    'model_version' => 'rule-v1',
                ]);
            }
            $device->forceFill($upd)->save();
        });

        return count($rows);
    }
}
