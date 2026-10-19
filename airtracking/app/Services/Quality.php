<?php

namespace App\Services;

use App\Models\ParameterLimit;
use Illuminate\Support\Facades\Cache;

/**
 * Aturan kualitas air: rentang normal (dari tabel parameter_limits), status per parameter,
 * dan prediksi sederhana. Prediksi di sini PLACEHOLDER berbasis aturan; ganti isi predict()
 * dengan pemanggilan model ML (COD/TSS) bila sudah siap.
 */
class Quality
{
    /** kunci JSON/dashboard => kolom di tabel measurements */
    public const COLUMNS = [
        'ph' => 'ph', 'ec' => 'ec_us_cm', 'tds' => 'tds_ppm',
        'do' => 'do_mg_l', 'temp' => 'temp_c', 'turb' => 'turb_ntu',
    ];

    public const LABELS = ['aman', 'waspada', 'tercemar'];

    /** @return array<string, array{name:string,unit:string,lo:float,hi:float,sides:string,dec:int}> */
    public static function limits(): array
    {
        return Cache::remember('parameter_limits', 60, function () {
            $out = [];
            foreach (ParameterLimit::orderBy('id')->get() as $m) {
                $out[$m->param] = [
                    'name' => $m->name, 'unit' => $m->unit, 'lo' => $m->lo, 'hi' => $m->hi,
                    'sides' => $m->sides, 'dec' => $m->decimals,
                ];
            }
            return $out;
        });
    }

    public static function flushLimits(): void
    {
        Cache::forget('parameter_limits');
    }

    /** ok | warn | crit | null (tidak ada nilai). warn = dalam 10% rentang dari batas. */
    public static function status(string $param, ?float $v): ?string
    {
        $p = self::limits()[$param] ?? null;
        if ($v === null || $p === null) {
            return null;
        }
        if ($v < $p['lo'] || $v > $p['hi']) {
            return 'crit';
        }
        $m = ($p['hi'] - $p['lo']) * 0.1;
        if ((str_contains($p['sides'], 'l') && $v < $p['lo'] + $m) || (str_contains($p['sides'], 'h') && $v > $p['hi'] - $m)) {
            return 'warn';
        }
        return 'ok';
    }

    /** @return array<string, ?string>|null */
    public static function statuses(?array $reading): ?array
    {
        if (!$reading) {
            return null;
        }
        $out = [];
        foreach (array_keys(self::COLUMNS) as $k) {
            $v = $reading[$k] ?? null;
            $out[$k] = self::status($k, $v === null ? null : (float) $v);
        }
        return $out;
    }

    /** @return array{label:string, probs:array<string,float>} */
    public static function predict(array $reading): array
    {
        $pen = 0.0;
        foreach (self::statuses($reading) ?? [] as $s) {
            $pen += $s === 'crit' ? 2 : ($s === 'warn' ? 0.8 : 0);
        }
        $sc = [2.2 - $pen * 1.1, 0.8 + min($pen, 2.2) * 0.5 - abs($pen - 1.4) * 0.2, -0.6 + $pen * 0.9];
        $ex = array_map('exp', $sc);
        $sum = array_sum($ex);

        $probs = [];
        foreach (self::LABELS as $i => $label) {
            $probs[$label] = round($ex[$i] / $sum, 4);
        }
        $best = array_keys($probs, max($probs))[0];

        return ['label' => $best, 'probs' => $probs];
    }
}
