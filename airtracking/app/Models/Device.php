<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Device extends Model
{
    protected $fillable = ['code', 'name', 'location_code', 'latitude', 'longitude', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
            'last_reading' => 'array',
            'last_seen_at' => 'datetime',
            'ph_calibrated_on' => 'date',
            'ec_calibrated_on' => 'date',
        ];
    }

    public function credential(): HasOne
    {
        return $this->hasOne(DeviceCredential::class);
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(Measurement::class);
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class);
    }

    /** Simpan credential baru (hanya hash-nya). Teks aslinya ditampilkan ke admin satu kali saja. */
    public function setCredential(string $plain): void
    {
        $this->credential()->updateOrCreate([], [
            'key_hash' => hash('sha256', $plain),
            'key_hint' => substr($plain, -4),
            'rotated_at' => now(),
        ]);
    }
}
