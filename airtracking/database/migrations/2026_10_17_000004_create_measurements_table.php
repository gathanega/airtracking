<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('location_code', 32)->nullable();
            $table->timestamp('measured_at');                    // waktu pengukuran dari RTC/NTP alat (UTC)
            $table->timestamp('received_at')->useCurrent();      // waktu server menerima
            $table->string('source', 8)->default('http');        // http | mqtt | sd

            // Kolom tidak memakai kata cadangan SQL (mis. "do"). Kunci JSON tetap: ph, ec, tds, do, temp, turb.
            $table->float('ph')->nullable();
            $table->float('ec_us_cm')->nullable();               // EC, µS/cm
            $table->float('tds_ppm')->nullable();                // TDS, ppm
            $table->float('do_mg_l')->nullable();                // oksigen terlarut, mg/L
            $table->float('temp_c')->nullable();                 // suhu, °C
            $table->float('turb_ntu')->nullable();               // kekeruhan, NTU

            // Kirim ulang paket SD yang sama tidak menggandakan data (upsert).
            $table->unique(['device_id', 'measured_at']);
            $table->index('measured_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurements');
    }
};
