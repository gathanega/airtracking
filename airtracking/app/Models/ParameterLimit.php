<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParameterLimit extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['lo' => 'float', 'hi' => 'float', 'decimals' => 'integer'];
    }
}
