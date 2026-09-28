<?php

namespace Webkul\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Lead\Contracts\LeadConsentVersion as LeadConsentVersionContract;

class LeadConsentVersion extends Model implements LeadConsentVersionContract
{
    protected $table = 'lead_consent_versions';

    protected $fillable = [
        'lead_consent_id',
        'lead_id',
        'version_number',
        'status',
        'client_name',
        'client_phone',
        'client_email',
        'agent_name',
        'agent_npn',
        'agency_name',
        'consent_text',
        'signature_data',
        'signed_at',
        'ip_address',
        'user_agent',
        'pdf_path',
        'file_hash',
        'revoked_at',
        'revocation_reason',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'signed_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /**
     * Timestamp when version was superseded by a newer consent document.
     */
    public function getSupersededAtAttribute()
    {
        return $this->status === 'superseded' ? $this->updated_at : null;
    }

    /**
     * Alias accessor for file_hash / SHA-256 fingerprint.
     */
    public function getConsentSha256Attribute(): ?string
    {
        return $this->file_hash;
    }

    /**
     * Parent LeadConsent relation.
     */
    public function consent(): BelongsTo
    {
        return $this->belongsTo(LeadConsentProxy::modelClass(), 'lead_consent_id');
    }

    /**
     * Parent Lead relation.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(LeadProxy::modelClass(), 'lead_id');
    }

    /**
     * Generate deterministic SHA-256 integrity hash for CMS compliance audit.
     */
    public static function generateHash(
        string $signatureData,
        string $consentText,
        string $clientName,
        string $signedAtIso,
        ?string $ipAddress = null
    ): string {
        $payload = implode('|', [
            trim($clientName),
            trim($consentText),
            trim($signatureData),
            trim($signedAtIso),
            trim((string) $ipAddress),
        ]);

        return hash('sha256', $payload);
    }

    /**
     * Verify whether the stored file_hash matches current record contents (tamper check).
     */
    public function verifyIntegrity(): bool
    {
        if (empty($this->file_hash) || empty($this->signature_data) || ! $this->signed_at) {
            return false;
        }

        $expectedHash = self::generateHash(
            (string) $this->signature_data,
            (string) $this->consent_text,
            (string) $this->client_name,
            $this->signed_at->toIso8601String(),
            $this->ip_address
        );

        return hash_equals($this->file_hash, $expectedHash);
    }
}
