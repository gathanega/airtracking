<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prediction extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['probabilities' => 'array'];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
