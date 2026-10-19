<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            // Titik lokasi alat di peta (derajat desimal, WGS84). Kosong = belum ditandai.
            $table->decimal('latitude', 9, 6)->nullable()->after('location_code');
            $table->decimal('longitude', 9, 6)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
