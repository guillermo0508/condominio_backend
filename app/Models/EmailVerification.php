<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailVerification extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'email',
        'expires_at',
        'verified',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        return !$this->verified && now()->isBefore($this->expires_at);
    }

    public function markAsVerified(): void
    {
        $this->update(['verified' => true]);
    }
}
