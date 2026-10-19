<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('measurement_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label', 10);                         // aman | waspada | tercemar
            $table->json('probabilities');                       // {"aman":0.71,"waspada":0.22,"tercemar":0.07}
            $table->float('cod_mg_l')->nullable();               // target model ML (menyusul)
            $table->float('tss_mg_l')->nullable();               // target model ML (menyusul)
            $table->string('model_version', 40)->default('rule-v1');
            $table->timestamps();

            $table->index(['device_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};
