<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Measurement extends Model
{
    public $timestamps = false;   // memakai measured_at dan received_at
    protected $guarded = [];

    protected function casts(): array
    {
        return ['measured_at' => 'datetime', 'received_at' => 'datetime'];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
