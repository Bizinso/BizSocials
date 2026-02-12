<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\CompanySize;
use App\Enums\Tenant\VerificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TenantProfile Model
 *
 * Represents the business profile information for a tenant.
 * Includes legal name, address, tax information, and verification status.
 *
 * @property string $id UUID primary key
 * @property string $tenant_id Parent tenant UUID
 * @property string|null $legal_name Legal business name
 * @property string|null $business_type Type of business
 * @property string|null $industry Industry sector
 * @property CompanySize|null $company_size Company size category
 * @property string|null $website Business website URL
 * @property string|null $address_line1 Street address line 1
 * @property string|null $address_line2 Street address line 2
 * @property string|null $city City name
 * @property string|null $state State/province name
 * @property string|null $country ISO 3166-1 alpha-2 country code
 * @property string|null $postal_code Postal/ZIP code
 * @property string|null $phone Phone number
 * @property string|null $gstin India GST number
 * @property string|null $pan India PAN number
 * @property string|null $tax_id International tax ID
 * @property VerificationStatus $verification_status Verification status
 * @property \Carbon\Carbon|null $verified_at Verification timestamp
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Tenant $tenant
 */
final class TenantProfile extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tenant_profiles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'legal_name',
        'business_type',
        'industry',
        'company_size',
        'website',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'country',
        'postal_code',
        'phone',
        'gstin',
        'pan',
        'tax_id',
        'verification_status',
        'verified_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'company_size' => CompanySize::class,
            'verification_status' => VerificationStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    /**
     * Get the parent tenant.
     *
     * @return BelongsTo<Tenant, TenantProfile>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if the profile is verified.
     */
    public function isVerified(): bool
    {
        return $this->verification_status === VerificationStatus::VERIFIED;
    }

    /**
     * Mark the profile as verified.
     */
    public function markAsVerified(): void
    {
        $this->verification_status = VerificationStatus::VERIFIED;
        $this->verified_at = now();
        $this->save();
    }

    /**
     * Mark the profile as verification failed.
     */
    public function markAsFailed(): void
    {
        $this->verification_status = VerificationStatus::FAILED;
        $this->save();
    }

    /**
     * Get the full formatted address.
     */
    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Check if the profile has any tax information.
     */
    public function hasTaxInfo(): bool
    {
        return ! empty($this->gstin)
            || ! empty($this->pan)
            || ! empty($this->tax_id);
    }
}
