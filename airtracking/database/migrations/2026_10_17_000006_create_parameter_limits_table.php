<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rentang normal per parameter (baku mutu). Admin bisa mengubahnya tanpa mengedit kode.
        Schema::create('parameter_limits', function (Blueprint $table) {
            $table->id();
            $table->string('param', 8)->unique();                // ph | ec | tds | do | temp | turb
            $table->string('name', 30);
            $table->string('unit', 12)->default('');
            $table->float('lo');
            $table->float('hi');
            $table->string('sides', 2);                          // l = waspada dekat batas bawah, h = batas atas, lh = keduanya
            $table->unsignedTinyInteger('decimals')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parameter_limits');
    }
};
