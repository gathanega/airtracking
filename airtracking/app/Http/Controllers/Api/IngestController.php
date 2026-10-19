<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\MeasurementIngestor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /api/ingest : alat mengirim data. Format sama dengan firmware:
 *   {"id":"AIR-01","key":"<credential>","loc":"LOK-01","ts":"2026-10-15T08:30:00Z",
 *    "ph":7.1,"ec":420,"tds":210,"do":6.8,"temp":27.5,"turb":3.2}
 * atau banyak data dari SD: {"id":..,"key":..,"loc":..,"records":[{"ts":..,"ph":..}, ...]}
 * Field status opsional: fw, bat (0-100), cal_ph, cal_ec (tanggal), sd_pending.
 */
class IngestController extends Controller
{
    public function store(Request $request, MeasurementIngestor $ingestor): JsonResponse
    {
        $data = $request->validate(array_merge([
            'id' => 'required|string|max:32',
            'key' => 'required|string|max:128',
            'loc' => 'nullable|string|max:32',
            'fw' => 'nullable|string|max:20',
            'bat' => 'nullable|integer|between:0,100',
            'cal_ph' => 'nullable|date',
            'cal_ec' => 'nullable|date',
            'sd_pending' => 'nullable|integer|min:0|max:100000',
            'records' => 'nullable|array|max:500',
        ], $this->sensorRules(''), $this->sensorRules('records.*.')));

        $device = Device::with('credential')->where('code', $data['id'])->where('is_active', true)->first();

        // Bandingkan hash walau alat tidak ada, supaya waktu respons tidak membocorkan ID yang valid.
        $stored = $device?->credential?->key_hash ?? str_repeat('0', 64);
        $valid = hash_equals($stored, hash('sha256', $data['key']));

        if (!$device || !$valid) {
            return response()->json(['message' => 'ID alat atau credential salah, atau alat belum diregistrasi'], 401);
        }

        return response()->json(['ok' => true, 'saved' => $ingestor->ingest($device, $data, 'http')]);
    }

    /** @return array<string,string> */
    private function sensorRules(string $p): array
    {
        return [
            $p.'ts' => 'nullable|date',
            $p.'ph' => 'nullable|numeric|between:0,14',
            $p.'ec' => 'nullable|numeric|between:0,100000',
            $p.'tds' => 'nullable|numeric|between:0,100000',
            $p.'do' => 'nullable|numeric|between:0,60',
            $p.'temp' => 'nullable|numeric|between:-5,100',
            $p.'turb' => 'nullable|numeric|between:0,10000',
        ];
    }
}
