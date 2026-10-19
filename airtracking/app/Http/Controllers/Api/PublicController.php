<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Measurement;
use App\Services\Quality;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** API JSON publik (tanpa login, hanya baca). Credential alat tidak pernah ikut. */
class PublicController extends Controller
{
    public function limits(): JsonResponse
    {
        return response()->json(Quality::limits());
    }

    public function devices(): JsonResponse
    {
        $list = Device::where('is_active', true)->orderBy('code')->get()->map(fn ($d) => $this->present($d));
        return response()->json($list);
    }

    public function device(string $code): JsonResponse
    {
        return response()->json($this->present($this->find($code)));
    }

    public function history(Request $request, string $code): JsonResponse
    {
        $v = $request->validate(['days' => 'integer|between:1,365', 'limit' => 'integer|between:1,1000']);
        $device = $this->find($code);
        $days = (int) ($v['days'] ?? 7);

        $rows = $this->rows($device, $days, (int) ($v['limit'] ?? 500))->map(fn ($m) => $this->row($m))->values();

        return response()->json(['id' => $device->code, 'days' => $days, 'data' => $rows]);
    }

    public function historyCsv(Request $request, string $code): StreamedResponse
    {
        $v = $request->validate(['days' => 'integer|between:1,365']);
        $device = $this->find($code);
        $rows = $this->rows($device, (int) ($v['days'] ?? 30), 10000);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['waktu', ...array_keys(Quality::COLUMNS)], ',', '"', '\\');
            foreach ($rows as $m) {
                fputcsv($out, array_values($this->row($m)), ',', '"', '\\');
            }
            fclose($out);
        }, $device->code.'.csv', ['Content-Type' => 'text/csv']);
    }

    public function predict(string $code): JsonResponse
    {
        $device = $this->find($code);
        if (!$device->last_reading) {
            return response()->json(['message' => 'alat belum mengirim data'], 409);
        }

        $stored = $device->predictions()->latest('id')->first();
        $p = $stored
            ? ['label' => $stored->label, 'probs' => $stored->probabilities, 'model' => $stored->model_version]
            : Quality::predict($device->last_reading) + ['model' => 'rule-v1'];

        return response()->json([
            'id' => $device->code,
            'label' => $p['label'],
            'model' => $p['model'],
            'probs' => collect($p['probs'])->map(fn ($v, $k) => ['label' => $k, 'p' => $v])->values(),
            'basis_ts' => $device->last_reading['ts'] ?? null,
        ]);
    }

    private function find(string $code): Device
    {
        return Device::where('code', $code)->where('is_active', true)->firstOrFail();
    }

    private function rows(Device $device, int $days, int $limit)
    {
        return $device->measurements()
            ->where('measured_at', '>=', now()->subDays($days))
            ->orderByDesc('measured_at')->limit($limit)->get()->reverse();
    }

    /** @return array<string,mixed> */
    private function row(Measurement $m): array
    {
        $out = ['measured_at' => $m->measured_at->toIso8601ZuluString()];
        foreach (Quality::COLUMNS as $json => $col) {
            $out[$json] = $m->{$col};
        }
        return $out;
    }

    /** @return array<string,mixed> */
    private function present(Device $d): array
    {
        return [
            'id' => $d->code,
            'name' => $d->name,
            'loc_code' => $d->location_code,
            'lat' => $d->latitude,
            'lng' => $d->longitude,
            'last' => $d->last_reading,
            'last_seen' => $d->last_seen_at?->toIso8601ZuluString(),
            'status' => Quality::statuses($d->last_reading),
            'battery_pct' => $d->battery_pct,
            'firmware' => $d->firmware_version,
            'cal_ph' => $d->ph_calibrated_on?->toDateString(),
            'cal_ec' => $d->ec_calibrated_on?->toDateString(),
            'sd_pending' => $d->sd_pending,
        ];
    }
}
