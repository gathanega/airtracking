<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();          // ID alat, mis. AIR-01 (dipakai di JSON dan topik MQTT)
            $table->string('name', 100);
            $table->string('location_code', 32)->index();  // kode lokasi, mis. LOK-01
            $table->boolean('is_active')->default(true);

            // Status alat yang dilaporkan firmware (opsional di payload)
            $table->string('firmware_version', 20)->nullable();
            $table->unsignedTinyInteger('battery_pct')->nullable();
            $table->date('ph_calibrated_on')->nullable();
            $table->date('ec_calibrated_on')->nullable();
            $table->unsignedInteger('sd_pending')->default(0); // data di microSD yang belum terkirim

            // Salinan bacaan terakhir agar dashboard tidak perlu query tabel besar
            $table->json('last_reading')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
