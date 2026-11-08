<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// P0-04: penanda bahwa timestamp dari alat dikoreksi server (mis. jam alat jauh di masa depan).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('measurements', function (Blueprint $table) {
            $table->boolean('ts_corrected')->default(false)->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('measurements', function (Blueprint $table) {
            $table->dropColumn('ts_corrected');
        });
    }
};
