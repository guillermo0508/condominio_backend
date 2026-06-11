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

    /**
     * Get the user that owns the email verification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if code is valid and not expired
     */
    public function isValid(): bool
    {
        return !$this->verified && now()->isBefore($this->expires_at);
    }

    /**
     * Mark as verified
     */
    public function markAsVerified(): void
    {
        $this->update(['verified' => true]);
    }
}
