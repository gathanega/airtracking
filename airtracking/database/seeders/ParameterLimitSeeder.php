<?php

namespace Database\Seeders;

use App\Models\ParameterLimit;
use Illuminate\Database\Seeder;

class ParameterLimitSeeder extends Seeder
{
    /** Acuan awal; sesuaikan dengan baku mutu penelitian lewat menu admin "Rentang normal". */
    public function run(): void
    {
        $rows = [
            ['ph',   'pH',        '',      6.5, 8.5,  'lh', 2],
            ['ec',   'EC',        'µS/cm', 0,   1500, 'h',  0],
            ['tds',  'TDS',       'ppm',   0,   500,  'h',  0],
            ['do',   'DO',        'mg/L',  5,   14,   'l',  2],
            ['temp', 'Suhu',      '°C',    20,  32,   'lh', 1],
            ['turb', 'Kekeruhan', 'NTU',   0,   5,    'h',  1],
        ];
        foreach ($rows as [$param, $name, $unit, $lo, $hi, $sides, $dec]) {
            ParameterLimit::updateOrCreate(['param' => $param], compact('name', 'unit', 'lo', 'hi', 'sides') + ['decimals' => $dec]);
        }
    }
}
