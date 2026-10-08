<?php

namespace Webkul\Teamwork\Support;

use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * The product's own name and support channels (Configuration › General ›
 * Brand), used instead of the upstream project's branding.
 */
class Brand
{
    public const DEFAULT_NAME = 'SegurosCRM';

    public static function name(): string
    {
        return (string) (static::get('name') ?: static::DEFAULT_NAME);
    }

    /**
     * Cards for the Help page: the agency's support, then in-app guides.
     */
    public static function support(): array
    {
        $email = static::get('support_email');
        $phone = static::get('support_phone');
        $url = static::get('support_url');

        $contact = $url ?: ($email ? 'mailto:'.$email : (Route::has('admin.teamwork.center') ? route('admin.teamwork.center') : '#'));
        $card = fn ($icon, $key, $route, $label) => [
            'icon' => $icon,
            'title' => "teamwork::app.help.cards.{$key}.title",
            'description' => "teamwork::app.help.cards.{$key}.description",
            'url' => Route::has($route) ? route($route) : '#',
            'url_label' => trans($label),
        ];

        return [
            'contact_url' => $contact,
            'services' => array_values(array_filter([
                [
                    'icon' => 'support',
                    'title' => 'teamwork::app.help.cards.support.title',
                    'description' => 'teamwork::app.help.cards.support.description',
                    'url' => $contact,
                    'url_label' => trim(implode(' · ', array_filter([$email, $phone, $url]))) ?: trans('teamwork::app.help.cards.support.none'),
                ],
                $card('services', 'playbook', 'admin.settings.teamwork.playbook.index', 'teamwork::app.playbook.title'),
                $card('extensions', 'sequences', 'admin.communications.sequences.index', 'communications::app.sequences.title'),
            ])),
            'resources' => [
                $card('docs', 'follow-up', 'admin.teamwork.center', 'teamwork::app.center.title'),
                $card('community', 'campaigns', 'admin.communications.campaigns.index', 'communications::app.campaigns.title'),
                $card('api', 'templates', 'admin.communications.templates.index', 'communications::app.templates.title'),
            ],
            // No upstream forums or videos.
            'community' => [],
        ];
    }

    protected static function get(string $field): ?string
    {
        try {
            $value = core()->getConfigData('general.general.brand.'.$field);
        } catch (Throwable) {
            return null;
        }

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
