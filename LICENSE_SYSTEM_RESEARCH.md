# License Management System Research & Analysis

## Executive Summary

This document analyzes popular software licensing systems used by successful commercial packages and plugins, with a focus on Laravel packages (Spatie), WordPress plugins (Freemius, EDD), and modern SaaS licensing platforms (LemonSqueezy).

---

## 1. Spatie's Approach (Laravel Packages)

### Overview
Spatie (creators of MediaLibrary Pro, Mailcoach, Ray) uses a sophisticated private Composer repository system with dynamic license validation.

### Architecture

**Components:**
1. **Private Composer Repository** - Using Satis (self-hosted Packagist)
2. **HTTP Basic Authentication** - License key as password
3. **Laravel API Backend** - Dynamic validation
4. **Single Source of Truth** - Main spatie.be application

### How It Works

```
┌─────────────────┐
│  Customer Site  │
│  composer.json  │
│  auth.json      │
└────────┬────────┘
         │
         │ 1. composer install
         │    Authorization: Basic {email}:{license_key}
         ▼
┌─────────────────┐
│  Satis Server   │
│ satis.spatie.be │
└────────┬────────┘
         │
         │ 2. Validate license
         │    GET /api/validate-license
         ▼
┌─────────────────┐
│  spatie.be API  │
│  Laravel App    │
│  License Model  │
└─────────────────┘
```

### Implementation Details

**1. Customer Setup:**
```json
// composer.json
{
  "repositories": [
    {
      "type": "composer",
      "url": "https://satis.spatie.be"
    }
  ],
  "require": {
    "spatie/laravel-medialibrary-pro": "^6.0"
  }
}

// auth.json
{
  "http-basic": {
    "satis.spatie.be": {
      "username": "customer@email.com",
      "password": "license-key-here"
    }
  }
}
```

**2. License Validation API:**
```php
// app/Http/Controllers/Api/ValidateLicenseController.php
public function __invoke(Request $request)
{
    $licenseKey = $request->getPassword(); // From Basic Auth
    $email = $request->getUser();

    // Check if license exists and is valid
    $license = License::where('key', $licenseKey)
        ->where('email', $email)
        ->first();

    if (! $license) {
        // Check other licenses owned by this user
        // (cross-license support)
        $license = License::where('email', $email)
            ->where('status', 'active')
            ->first();
    }

    if (! $license || ! $license->isValid()) {
        abort(401);
    }

    return response()->json([
        'valid' => true,
        'expires_at' => $license->expires_at,
    ]);
}
```

**3. Satis Configuration:**
```json
{
  "name": "Spatie Private Packages",
  "homepage": "https://satis.spatie.be",
  "repositories": [
    { "type": "vcs", "url": "https://github.com/spatie/laravel-medialibrary-pro" }
  ],
  "require-all": true,
  "archive": {
    "directory": "dist",
    "format": "tar",
    "skip-dev": true
  }
}
```

### Key Features

✅ **Seamless Developer Experience** - Uses standard Composer workflow
✅ **Dynamic Validation** - No need to rebuild Satis when licenses change
✅ **Single Source of Truth** - Main application manages all licenses
✅ **Cross-License Support** - Users with multiple products use one auth
✅ **Version Control** - 1 year of updates, then downloads freeze
✅ **Fallback Options** - Users can download zips and self-host

### Pros & Cons

**Pros:**
- Excellent developer experience
- Leverages existing Composer infrastructure
- No changes to user's code after license expires
- Can still use old versions after expiration
- Professional and trusted system

**Cons:**
- Requires Satis server setup and maintenance
- Cannot prevent code sharing (but can prevent updates)
- License key visible in auth.json file
- Requires users to understand Composer authentication

---

## 2. LemonSqueezy License API

### Overview
LemonSqueezy is a modern merchant of record platform with built-in license key management and validation API.

### Architecture

```
┌─────────────────┐
│   Your Plugin   │
│  (WordPress,    │
│   Laravel, etc) │
└────────┬────────┘
         │
         │ Activate/Validate/Deactivate
         ▼
┌─────────────────────────────────┐
│  LemonSqueezy License API       │
│  https://api.lemonsqueezy.com   │
└─────────────────────────────────┘
```

### API Endpoints

**1. Activate License:**
```
POST https://api.lemonsqueezy.com/v1/licenses/activate
Content-Type: application/json

{
  "license_key": "...",
  "instance_name": "MyApp on example.com"
}

Response:
{
  "activated": true,
  "license_key": {
    "id": 123,
    "status": "active",
    "key": "...",
    "activation_limit": 5,
    "activation_usage": 1,
    "created_at": "...",
    "expires_at": "..."
  },
  "instance": {
    "id": "...",
    "name": "MyApp on example.com",
    "created_at": "..."
  },
  "meta": {
    "store_id": 456,
    "product_id": 789,
    "variant_id": 101
  }
}
```

**2. Validate License:**
```
POST https://api.lemonsqueezy.com/v1/licenses/validate
Content-Type: application/json

{
  "license_key": "...",
  "instance_id": "..."
}

Response:
{
  "valid": true,
  "license_key": { ... },
  "instance": { ... },
  "meta": { ... }
}
```

**3. Deactivate License:**
```
POST https://api.lemonsqueezy.com/v1/licenses/deactivate
Content-Type: application/json

{
  "license_key": "...",
  "instance_id": "..."
}
```

### Security Best Practices

**Always verify product/store ID:**
```php
$response = Http::post('https://api.lemonsqueezy.com/v1/licenses/validate', [
    'license_key' => $licenseKey,
    'instance_id' => $instanceId,
]);

$data = $response->json();

// IMPORTANT: Prevent cross-product license usage
if ($data['meta']['product_id'] !== config('app.lemonsqueezy_product_id')) {
    throw new InvalidLicenseException('License key is for a different product');
}

if ($data['meta']['store_id'] !== config('app.lemonsqueezy_store_id')) {
    throw new InvalidLicenseException('License key is from a different store');
}
```

### Key Features

✅ **Activation Limits** - Restrict number of sites per license
✅ **Instance Management** - Track where licenses are active
✅ **Automatic Expiration** - Handles subscription renewals
✅ **Webhooks** - Real-time license status updates
✅ **No Infrastructure** - Fully managed by LemonSqueezy

### Pros & Cons

**Pros:**
- No server infrastructure needed
- Handles payments, taxes, invoicing automatically
- Built-in webhook system for real-time updates
- Activation limits prevent abuse
- Professional dashboard for customers

**Cons:**
- Requires internet connection to validate
- Relies on third-party service
- Transaction fees (5% + payment processing)
- Cannot self-host

---

## 3. Freemius (WordPress Standard)

### Overview
Freemius is the industry-standard licensing system used by thousands of WordPress plugins and themes.

### Architecture

```
┌──────────────────┐
│  WordPress Site  │
│  Your Plugin/    │
│  Theme + SDK     │
└────────┬─────────┘
         │
         │ Freemius SDK
         ▼
┌─────────────────────────────┐
│  Freemius API               │
│  https://api.freemius.com   │
└─────────────────────────────┘
```

### API Flow

**1. License Activation:**
```
POST https://api.freemius.com/v1/products/{product_id}/licenses/activate.json

Parameters:
- uid: {uuid}
- license_key: {license_key}
- site_url: {site_url}
- site_name: {site_name}

Response:
{
  "install_id": "...",
  "install_api_token": "...",
  "user": { ... },
  "license": {
    "id": "...",
    "key": "...",
    "is_active": true,
    "is_expired": false,
    "activations_left": 4,
    "created": "...",
    "expiration": "..."
  }
}
```

**2. License Validation:**
```
GET https://api.freemius.com/v1/products/{product_id}/installs/{install_id}/license.json

Parameters:
- uid: {uuid}
- license_key: {license_key}
- install_api_token: {install_api_token}

Response:
{
  "is_valid": true,
  "is_active": true,
  "is_expired": false,
  "activations_left": 4
}
```

### WordPress SDK Integration

```php
// Initialize Freemius SDK
$mediaman_fs = fs_dynamic_init([
    'id'                  => '123',
    'slug'                => 'mediaman',
    'type'                => 'plugin',
    'public_key'          => 'pk_xxx',
    'is_premium'          => true,
    'has_addons'          => false,
    'has_paid_plans'      => true,
    'menu'                => [
        'slug'    => 'mediaman',
        'support' => false,
    ],
]);

// Check if user has valid license
if ($mediaman_fs->can_use_premium_code()) {
    // Premium features enabled
}

// Check specific license
if ($mediaman_fs->is_plan('professional', true)) {
    // Professional plan features
}
```

### Key Features

✅ **WordPress-Native** - Seamless WordPress integration
✅ **Auto-Updates** - Handles plugin/theme updates
✅ **License Management UI** - Built-in settings pages
✅ **Private Key Authentication** - Secure communication
✅ **Trial Support** - Built-in trial period handling
✅ **Multi-Site Support** - Network license management

### Pros & Cons

**Pros:**
- Industry standard for WordPress
- Comprehensive SDK with UI components
- Handles payments, licensing, updates in one system
- Excellent documentation and support
- Built-in analytics dashboard

**Cons:**
- WordPress-specific (not for Laravel)
- Revenue share model (25% of sales)
- Limited customization of licensing logic
- Customers see Freemius branding

---

## 4. EDD Software Licensing (WordPress Self-Hosted)

### Overview
Easy Digital Downloads (EDD) with Software Licensing extension provides a self-hosted WordPress solution for selling and licensing digital products.

### Architecture

```
┌─────────────────┐
│  Customer Site  │
│  Your Plugin    │
└────────┬────────┘
         │
         │ License API Requests
         ▼
┌─────────────────────────────────┐
│  Your WordPress Site            │
│  EDD + Software Licensing       │
│  https://yoursite.com           │
└─────────────────────────────────┘
```

### API Endpoints

**1. Activate License:**
```
GET https://yoursite.com/?edd_action=activate_license&item_id=8&license=xxx&url=http://licensedsite.com

Response:
{
  "success": true,
  "license": "valid",
  "item_id": 8,
  "item_name": "MediaMan Pro",
  "license_limit": 5,
  "site_count": 1,
  "activations_left": 4,
  "expires": "2025-12-31 23:59:59",
  "payment_id": 123,
  "customer_name": "John Doe",
  "customer_email": "john@example.com"
}
```

**2. Check License:**
```
GET https://yoursite.com/?edd_action=check_license&item_id=8&license=xxx&url=http://licensedsite.com

Response:
{
  "license": "valid",
  "item_id": 8,
  "item_name": "MediaMan Pro",
  "expires": "2025-12-31 23:59:59",
  "payment_id": 123,
  "customer_name": "John Doe",
  "customer_email": "john@example.com",
  "license_limit": 5,
  "site_count": 1,
  "activations_left": 4
}
```

**3. Deactivate License:**
```
GET https://yoursite.com/?edd_action=deactivate_license&item_id=8&license=xxx&url=http://licensedsite.com
```

**4. Get Version:**
```
GET https://yoursite.com/?edd_action=get_version&item_id=8&license=xxx&url=http://licensedsite.com

Response:
{
  "new_version": "1.5.0",
  "stable_version": "1.4.5",
  "name": "MediaMan Pro",
  "slug": "mediaman-pro",
  "url": "https://yoursite.com/downloads/mediaman-pro/",
  "homepage": "https://yoursite.com/",
  "package": "https://yoursite.com/?edd_action=package_download&...",
  "download_link": "https://yoursite.com/?edd_action=package_download&...",
  "sections": {
    "description": "...",
    "changelog": "..."
  },
  "banners": { ... },
  "icons": { ... }
}
```

### PHP Client Implementation

```php
class EDD_License_Handler
{
    private $api_url;
    private $item_id;
    private $license_key;

    public function activate($site_url)
    {
        $response = wp_remote_post($this->api_url, [
            'body' => [
                'edd_action' => 'activate_license',
                'license'    => $this->license_key,
                'item_id'    => $this->item_id,
                'url'        => $site_url,
            ],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $license_data = json_decode(wp_remote_retrieve_body($response));

        if ($license_data->license === 'valid') {
            update_option('mediaman_license_status', 'valid');
            update_option('mediaman_license_data', $license_data);
            return true;
        }

        return false;
    }

    public function check()
    {
        $response = wp_remote_post($this->api_url, [
            'body' => [
                'edd_action' => 'check_license',
                'license'    => $this->license_key,
                'item_id'    => $this->item_id,
                'url'        => home_url(),
            ],
        ]);

        $license_data = json_decode(wp_remote_retrieve_body($response));

        return $license_data->license === 'valid';
    }
}
```

### Key Features

✅ **Self-Hosted** - Full control over licensing system
✅ **No Revenue Share** - Keep 100% of sales (minus payment processing)
✅ **Customizable** - Extensive hooks and filters
✅ **Auto-Updates** - Built-in update mechanism
✅ **Unlimited Products** - No per-product fees
✅ **Customer Portal** - License management for customers

### Pros & Cons

**Pros:**
- Complete control and ownership
- No recurring fees or revenue sharing
- Highly customizable
- WordPress-native integration
- Extensive documentation and community

**Cons:**
- Requires WordPress installation
- You handle all infrastructure
- You handle all payments/taxes yourself (unless using payment gateway)
- Requires maintenance and updates
- Not suitable for non-WordPress products

---

## 5. General Best Practices

### License Key Generation

**RSA-Based Signing (Most Secure):**

```php
class LicenseKeyGenerator
{
    private $privateKey;
    private $publicKey;

    public function __construct()
    {
        $this->privateKey = openssl_pkey_get_private(
            file_get_contents(storage_path('keys/private.pem'))
        );

        $this->publicKey = openssl_pkey_get_public(
            file_get_contents(storage_path('keys/public.pem'))
        );
    }

    public function generate($domain, $productId, $expiresAt)
    {
        $data = json_encode([
            'domain' => $domain,
            'product_id' => $productId,
            'expires_at' => $expiresAt,
            'generated_at' => now()->timestamp,
        ]);

        openssl_sign($data, $signature, $this->privateKey, OPENSSL_ALGO_SHA256);

        $licenseKey = base64_encode($data . '::' . $signature);

        return $licenseKey;
    }

    public function verify($licenseKey)
    {
        $decoded = base64_decode($licenseKey);
        [$data, $signature] = explode('::', $decoded);

        $verified = openssl_verify($data, $signature, $this->publicKey, OPENSSL_ALGO_SHA256);

        if ($verified !== 1) {
            return false;
        }

        $licenseData = json_decode($data, true);

        // Check expiration
        if ($licenseData['expires_at'] < now()->timestamp) {
            return false;
        }

        return $licenseData;
    }
}
```

**UUID-Based (Simpler):**

```php
use Illuminate\Support\Str;

class SimpleLicenseGenerator
{
    public function generate()
    {
        return Str::uuid()->toString();
    }

    public function verify($key)
    {
        return License::where('key', $key)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }
}
```

### Security Considerations

**1. Rate Limiting:**
```php
// Prevent brute force license validation
RateLimiter::for('license-validation', function (Request $request) {
    return Limit::perMinute(10)->by($request->ip());
});
```

**2. IP Whitelisting (Optional):**
```php
// Store allowed IPs with license
$license->allowed_ips = ['192.168.1.1', '10.0.0.1'];

// Validate
if (!in_array($request->ip(), $license->allowed_ips)) {
    throw new UnauthorizedException();
}
```

**3. Domain Verification:**
```php
// Store allowed domains
$license->allowed_domains = ['example.com', 'staging.example.com'];

// Validate
$requestDomain = parse_url($request->input('site_url'), PHP_URL_HOST);

if (!in_array($requestDomain, $license->allowed_domains)) {
    throw new UnauthorizedException();
}
```

**4. Fingerprinting:**
```php
// Create unique installation fingerprint
$fingerprint = hash('sha256', implode('|', [
    $request->ip(),
    $request->userAgent(),
    $request->input('site_url'),
]));

// Store on activation
$activation->fingerprint = $fingerprint;

// Verify on subsequent requests
if ($activation->fingerprint !== $fingerprint) {
    // Potentially compromised
}
```

### Database Schema

```php
// licenses table
Schema::create('licenses', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->string('key')->unique();
    $table->foreignId('user_id')->constrained();
    $table->foreignId('product_id')->constrained();
    $table->string('status'); // active, expired, suspended, cancelled
    $table->integer('activation_limit')->default(1);
    $table->integer('activation_count')->default(0);
    $table->timestamp('expires_at')->nullable();
    $table->timestamp('last_checked_at')->nullable();
    $table->timestamps();

    $table->index(['key', 'status']);
    $table->index('expires_at');
});

// license_activations table
Schema::create('license_activations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('license_id')->constrained()->onDelete('cascade');
    $table->string('instance_id')->unique();
    $table->string('instance_name');
    $table->string('site_url');
    $table->string('ip_address');
    $table->string('fingerprint')->nullable();
    $table->json('meta')->nullable(); // Server info, PHP version, etc.
    $table->timestamp('activated_at');
    $table->timestamp('last_checked_at')->nullable();
    $table->timestamps();

    $table->index('instance_id');
});
```

---

## 6. Recommended Approach for MediaMan

Based on the research, here's my recommendation for MediaMan:

### Option A: Spatie-Style (Recommended for Laravel Devs)

**Best For:** Professional Laravel developers
**Implementation Complexity:** High
**Control:** Maximum
**Cost:** Infrastructure only

**Architecture:**
1. Private Composer repository (Satis)
2. HTTP Basic Auth with license keys
3. Laravel API for dynamic validation
4. Optional: Zip download fallback

**Pros:**
- Professional developer experience
- Leverages Composer workflow
- Excellent for Laravel ecosystem
- No revenue sharing

**Cons:**
- Requires Satis infrastructure
- More complex setup

### Option B: LemonSqueezy (Recommended for Quick Launch)

**Best For:** Quick market validation, SaaS model
**Implementation Complexity:** Low
**Control:** Medium
**Cost:** 5% + payment processing

**Architecture:**
1. LemonSqueezy handles sales & licensing
2. Simple API validation in your package
3. Webhooks for real-time updates

**Pros:**
- Fastest to market
- No infrastructure needed
- Handles payments, taxes, VAT
- Professional customer portal

**Cons:**
- Transaction fees
- Depends on third party
- Less customization

### Option C: Hybrid Approach (Best of Both Worlds)

**Combine Spatie's method with LemonSqueezy:**

1. Use LemonSqueezy for sales, checkout, customer management
2. Use private Composer repository for distribution
3. Validate LemonSqueezy licenses via API
4. Store activations in your database

**Benefits:**
- Professional sales/checkout experience
- Composer-based distribution
- Real-time license validation
- Webhook support for renewals

---

## 7. Implementation Roadmap

### Phase 1: Basic License Validation (Week 1-2)

```php
// Simple API-based validation
Route::post('/api/v1/licenses/validate', [LicenseController::class, 'validate'])
    ->middleware('throttle:10,1');

class LicenseController
{
    public function validate(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string',
            'domain' => 'required|url',
        ]);

        $license = License::where('key', $request->license_key)
            ->where('status', 'active')
            ->first();

        if (!$license) {
            return response()->json(['valid' => false], 401);
        }

        // Check if domain is allowed
        if (!$this->isDomainAllowed($license, $request->domain)) {
            return response()->json(['valid' => false, 'error' => 'Domain not authorized'], 403);
        }

        // Check expiration
        if ($license->isExpired()) {
            return response()->json(['valid' => false, 'error' => 'License expired'], 403);
        }

        return response()->json([
            'valid' => true,
            'license' => [
                'type' => $license->plan,
                'expires_at' => $license->expires_at,
                'features' => $license->features,
            ],
        ]);
    }
}
```

### Phase 2: Activation Management (Week 3-4)

```php
// Activation endpoint
Route::post('/api/v1/licenses/activate', [LicenseController::class, 'activate']);
Route::post('/api/v1/licenses/deactivate', [LicenseController::class, 'deactivate']);

class LicenseController
{
    public function activate(Request $request)
    {
        $license = License::where('key', $request->license_key)->firstOrFail();

        // Check activation limit
        if ($license->activations()->count() >= $license->activation_limit) {
            return response()->json(['error' => 'Activation limit reached'], 403);
        }

        $activation = $license->activations()->create([
            'instance_id' => Str::uuid(),
            'instance_name' => $request->instance_name,
            'site_url' => $request->domain,
            'ip_address' => $request->ip(),
            'activated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'instance_id' => $activation->instance_id,
        ]);
    }
}
```

### Phase 3: Private Composer Repository (Week 5-8)

Set up Satis with dynamic authentication against your license API.

### Phase 4: Customer Dashboard (Week 9-12)

Build customer portal for license management, downloads, invoices.

---

## 8. Cost-Benefit Analysis

| Approach | Setup Cost | Monthly Cost | Revenue Share | Control | Complexity |
|----------|-----------|--------------|---------------|---------|------------|
| **Spatie (Satis)** | $0 (DIY) | $10-50 (server) | 0% | ★★★★★ | ★★★★☆ |
| **LemonSqueezy** | $0 | $10 (plan) | 5% | ★★★☆☆ | ★★☆☆☆ |
| **Freemius** | $0 | $0 | 25% | ★★☆☆☆ | ★☆☆☆☆ |
| **EDD Self-Hosted** | $199 (plugin) | $10-50 (server) | 0% | ★★★★☆ | ★★★☆☆ |
| **Hybrid** | $0 | $20-60 | 5% | ★★★★☆ | ★★★☆☆ |

---

## Conclusion

For **MediaMan**, I recommend starting with **Option C (Hybrid)**:

1. **Use LemonSqueezy** for:
   - Checkout & payment processing
   - Customer management
   - License key generation
   - Invoicing & tax handling

2. **Build Custom** for:
   - License validation API
   - Activation management
   - Feature gating in package

3. **Later Add** (if needed):
   - Private Composer repository (Satis)
   - Customer dashboard
   - Download portal

This gives you the fastest path to market while maintaining control over the licensing logic and avoiding WordPress-specific solutions.

**Estimated Timeline:**
- Week 1-2: LemonSqueezy setup + Basic API
- Week 3-4: Package integration + Testing
- Week 5-6: Customer portal + Documentation
- Week 7+: Satis setup (optional)

**Total Cost:**
- $0 upfront
- $10/month (LemonSqueezy)
- 5% per transaction
- Keep 95% of revenue
