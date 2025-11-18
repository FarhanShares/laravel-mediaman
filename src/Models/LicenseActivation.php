<?php

namespace FarhanShares\MediaMan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseActivation extends Model
{
    protected $table = 'mediaman_license_activations';

    protected $fillable = [
        'license_id',
        'instance_id',
        'instance_name',
        'site_url',
        'site_domain',
        'ip_address',
        'fingerprint',
        'meta',
        'activated_at',
        'last_checked_at',
        'deactivated_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'activated_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'deactivated_at' => 'datetime',
    ];

    /**
     * Get the license that owns this activation
     */
    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    /**
     * Check if activation is still active
     */
    public function isActive(): bool
    {
        return is_null($this->deactivated_at);
    }

    /**
     * Deactivate this activation
     */
    public function deactivate(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        $this->update(['deactivated_at' => now()]);
        $this->license->decrement('activation_count');

        return true;
    }

    /**
     * Update last checked timestamp
     */
    public function markAsChecked(): void
    {
        $this->update(['last_checked_at' => now()]);
    }

    /**
     * Verify fingerprint matches
     */
    public function verifyFingerprint(string $fingerprint): bool
    {
        return $this->fingerprint === $fingerprint;
    }

    /**
     * Scope to only active activations
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deactivated_at');
    }

    /**
     * Scope to filter by domain
     */
    public function scopeForDomain($query, string $domain)
    {
        return $query->where('site_domain', $domain);
    }
}
