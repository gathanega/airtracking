<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Terpisah dari devices supaya credential tidak pernah ikut di query atau JSON publik.
        Schema::create('device_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('key_hash', 64);        // SHA-256 (hex) dari credential; aslinya tidak disimpan
            $table->string('key_hint', 4);         // 4 karakter terakhir, untuk mengenali credential di panel admin
            $table->timestamp('rotated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_credentials');
    }
};
