<?php

namespace FarhanShares\MediaMan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class License extends Model
{
    use SoftDeletes;

    protected $table = 'mediaman_licenses';

    protected $fillable = [
        'uuid',
        'key',
        'email',
        'user_id',
        'product_id',
        'variant_id',
        'order_id',
        'store_id',
        'status',
        'type',
        'activation_limit',
        'activation_count',
        'features',
        'meta',
        'expires_at',
        'last_checked_at',
        'last_validated_at',
    ];

    protected $casts = [
        'features' => 'array',
        'meta' => 'array',
        'activation_limit' => 'integer',
        'activation_count' => 'integer',
        'expires_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'last_validated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($license) {
            if (empty($license->uuid)) {
                $license->uuid = (string) Str::uuid();
            }

            if (empty($license->key)) {
                $license->key = static::generateLicenseKey();
            }
        });
    }

    /**
     * Get the activations for this license
     */
    public function activations(): HasMany
    {
        return $this->hasMany(LicenseActivation::class);
    }

    /**
     * Get the active activations
     */
    public function activeActivations(): HasMany
    {
        return $this->activations()->whereNull('deactivated_at');
    }

    /**
     * Get the user who owns this license
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    /**
     * Check if the license is valid
     */
    public function isValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the license is expired
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if activation limit reached
     */
    public function hasReachedActivationLimit(): bool
    {
        return $this->activeActivations()->count() >= $this->activation_limit;
    }

    /**
     * Check if license has a specific feature
     */
    public function hasFeature(string $feature): bool
    {
        if (!$this->features) {
            return false;
        }

        return in_array($feature, $this->features);
    }

    /**
     * Activate license for a site
     */
    public function activate(string $siteUrl, string $instanceName, array $meta = []): LicenseActivation
    {
        if ($this->hasReachedActivationLimit()) {
            throw new \Exception('Activation limit reached');
        }

        $domain = parse_url($siteUrl, PHP_URL_HOST);

        $activation = $this->activations()->create([
            'instance_id' => (string) Str::uuid(),
            'instance_name' => $instanceName,
            'site_url' => $siteUrl,
            'site_domain' => $domain,
            'ip_address' => request()->ip(),
            'fingerprint' => $this->generateFingerprint($siteUrl, request()->userAgent()),
            'meta' => $meta,
            'activated_at' => now(),
        ]);

        $this->increment('activation_count');

        return $activation;
    }

    /**
     * Deactivate a specific activation
     */
    public function deactivate(string $instanceId): bool
    {
        $activation = $this->activations()
            ->where('instance_id', $instanceId)
            ->whereNull('deactivated_at')
            ->first();

        if (!$activation) {
            return false;
        }

        $activation->update(['deactivated_at' => now()]);
        $this->decrement('activation_count');

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
     * Update last validated timestamp
     */
    public function markAsValidated(): void
    {
        $this->update(['last_validated_at' => now()]);
    }

    /**
     * Suspend the license
     */
    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    /**
     * Cancel the license
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Reactivate the license
     */
    public function reactivate(): void
    {
        $this->update(['status' => 'active']);
    }

    /**
     * Generate a unique license key
     */
    public static function generateLicenseKey(): string
    {
        return strtoupper(Str::uuid()->toString());
    }

    /**
     * Generate fingerprint for activation
     */
    protected function generateFingerprint(string $siteUrl, ?string $userAgent = null): string
    {
        return hash('sha256', implode('|', [
            $siteUrl,
            $userAgent ?? '',
            request()->ip() ?? '',
        ]));
    }

    /**
     * Find license by key
     */
    public static function findByKey(string $key): ?self
    {
        return static::where('key', $key)->first();
    }

    /**
     * Find valid license by key
     */
    public static function findValidByKey(string $key): ?self
    {
        $license = static::findByKey($key);

        if (!$license || !$license->isValid()) {
            return null;
        }

        return $license;
    }
}
