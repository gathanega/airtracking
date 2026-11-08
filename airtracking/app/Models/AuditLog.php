<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Jejak audit aksi admin. Jangan pernah menyimpan credential, kata sandi, atau token di sini. */
class AuditLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['meta' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Catat satu aksi. User diambil dari sesi saat ini; untuk login gagal nilainya null.
     *
     * @param  array<string,mixed>  $meta  data ringkas tanpa rahasia
     */
    public static function record(string $action, array $meta = [], ?Model $subject = null): self
    {
        $request = app()->bound('request') ? request() : null;

        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'ip' => $request?->ip(),
            'meta' => $meta ?: null,
        ]);
    }
}
