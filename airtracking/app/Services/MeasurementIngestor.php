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
    /** Batas bawah timestamp yang masuk akal. RTC rusak sering mengirim 1970-01-01. */
    public const MIN_TS = '2025-01-01 00:00:00';

    /**
     * @param  array<string,mixed>  $data  payload tervalidasi; "records" opsional untuk paket banyak data
     * @return array{saved:int, skipped:int, ts_rejected:int, ts_corrected:int, heartbeat:bool}
     */
    public function ingest(Device $device, array $data, string $source = 'http'): array
    {
        $now = now()->utc();
        $stats = ['saved' => 0, 'skipped' => 0, 'ts_rejected' => 0, 'ts_corrected' => 0, 'heartbeat' => false];

        // records ada (walau []) => jangan jatuh ke payload datar. Ini yang dulu membuat baris kosong.
        $hasRecordsKey = array_key_exists('records', $data);
        $recs = $hasRecordsKey ? (array) ($data['records'] ?? []) : [$data];

        $rows = [];
        foreach (array_slice($recs, 0, 500) as $r) {
            if (! is_array($r) || ! $this->hasSensor($r)) {
                $stats['skipped']++;                       // record tanpa satu pun nilai sensor: abaikan

                continue;
            }

            $rawTs = $hasRecordsKey ? ($r['ts'] ?? null) : ($data['ts'] ?? null);
            $parsed = $this->timestamp($rawTs, $now, $stats);
            if ($parsed === null) {
                continue;                                  // timestamp terlalu lama: tolak record
            }
            [$ts, $tsCorrected] = $parsed;

            $key = $ts->toDateTimeString();
            $row = [
                'device_id' => $device->id,
                'location_code' => $data['loc'] ?? $device->location_code,
                'measured_at' => $key,
                'received_at' => $now->toDateTimeString(),
                'source' => $source,
                'ts_corrected' => $tsCorrected,
            ];
            foreach (Quality::COLUMNS as $json => $col) {
                $row[$col] = isset($r[$json]) ? (float) $r[$json] : null;
            }
            $rows[$key] = $row;                            // duplikat waktu dalam satu paket: yang terakhir dipakai
        }
        $rows = array_values($rows);
        $stats['saved'] = count($rows);

        // Tidak ada pengukuran valid: perlakukan sebagai heartbeat bila ada field status.
        if (! $rows) {
            if ($this->hasStatus($data)) {
                DB::transaction(function () use ($device, $data, $now) {
                    $device->forceFill($this->statusFields($data, $now))->save();
                });
                $stats['heartbeat'] = true;
            }

            return $stats;
        }

        DB::transaction(function () use ($device, $data, $rows, $now) {
            $update = array_merge(['source', 'location_code', 'received_at', 'ts_corrected'], array_values(Quality::COLUMNS));
            Measurement::upsert($rows, ['device_id', 'measured_at'], $update);

            usort($rows, fn ($a, $b) => strcmp($a['measured_at'], $b['measured_at']));
            $newest = end($rows);

            $upd = $this->statusFields($data, $now);

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

        return $stats;
    }

    /** Record dianggap pengukuran bila minimal satu parameter sensor terisi (0 tetap valid). */
    private function hasSensor(array $r): bool
    {
        foreach (array_keys(Quality::COLUMNS) as $json) {
            if (array_key_exists($json, $r) && $r[$json] !== null && $r[$json] !== '') {
                return true;
            }
        }

        return false;
    }

    /** Field status alat yang boleh diperbarui tanpa baris pengukuran (heartbeat). */
    private function hasStatus(array $data): bool
    {
        foreach (['fw', 'bat', 'cal_ph', 'cal_ec', 'sd_pending'] as $k) {
            if (array_key_exists($k, $data) && $data[$k] !== null) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string,mixed> */
    private function statusFields(array $data, Carbon $now): array
    {
        $upd = ['last_seen_at' => $now];
        foreach (['fw' => 'firmware_version', 'bat' => 'battery_pct', 'cal_ph' => 'ph_calibrated_on',
            'cal_ec' => 'ec_calibrated_on', 'sd_pending' => 'sd_pending'] as $in => $col) {
            if (isset($data[$in])) {
                $upd[$col] = $data[$in];
            }
        }

        return $upd;
    }

    /**
     * Parse timestamp. Kembalikan null bila tidak bisa dibaca atau terlalu lama (ditolak).
     * Timestamp yang terlalu jauh di masa depan dikoreksi ke waktu server dan ditandai.
     *
     * @return array{0: Carbon, 1: bool}|null [waktu UTC, apakah dikoreksi]
     */
    private function timestamp(mixed $raw, Carbon $now, array &$stats): ?array
    {
        if ($raw === null || $raw === '') {
            return [$now->copy(), false];
        }

        try {
            $ts = Carbon::parse($raw)->utc();
        } catch (\Throwable) {
            $stats['ts_rejected']++;

            return null;
        }

        if ($ts->lt(Carbon::parse(self::MIN_TS, 'UTC'))) {
            $stats['ts_rejected']++;

            return null;
        }

        if ($ts->gt($now->copy()->addDay())) {
            $stats['ts_corrected']++;

            return [$now->copy(), true];
        }

        return [$ts, false];
    }
}
