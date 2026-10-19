<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceCredential extends Model
{
    protected $fillable = ['device_id', 'key_hash', 'key_hint', 'rotated_at'];
    protected $hidden = ['key_hash'];

    protected function casts(): array
    {
        return ['rotated_at' => 'datetime'];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
