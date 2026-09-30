<?php

namespace Webkul\Communications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunicationTemplate extends Model
{
    public const CHANNELS = ['email', 'whatsapp', 'sms'];

    public const LOCALES = ['es', 'en'];

    public const CATEGORIES = ['welcome', 'benefits', 'care', 'renewal', 'retention', 'payment', 'documents', 'greeting', 'sales', 'general'];

    public const PURPOSES = ['transactional', 'marketing'];

    protected $table = 'communication_templates';

    protected $fillable = ['agency_id', 'code', 'name', 'category', 'purpose', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function contents(): HasMany
    {
        return $this->hasMany(TemplateContent::class, 'template_id');
    }

    /**
     * The version for a channel, in the wanted language or else any other.
     * SMS falls back to the WhatsApp text and vice versa (both are short).
     */
    public function contentFor(string $channel, string $locale): ?TemplateContent
    {
        $contents = $this->relationLoaded('contents') ? $this->contents : $this->contents()->get();
        $channels = match ($channel) {
            'sms' => ['sms', 'whatsapp'],
            'whatsapp' => ['whatsapp', 'sms'],
            default => [$channel],
        };

        foreach ($channels as $candidate) {
            $match = $contents->where('channel', $candidate);

            if ($found = $match->firstWhere('locale', $locale) ?? $match->first()) {
                return $found;
            }
        }

        return null;
    }

    /**
     * Channels this template has text for.
     */
    public function channels(): array
    {
        return $this->contents->pluck('channel')->unique()->values()->all();
    }
}
