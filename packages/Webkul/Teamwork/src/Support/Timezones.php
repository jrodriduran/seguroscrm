<?php

namespace Webkul\Teamwork\Support;

/**
 * Time zone choices for Configuration > General > Time zone: US zones
 * first (the agencies' market), then Latin America, then the rest.
 */
class Timezones
{
    const PREFERRED = [
        'America/New_York' => 'Eastern (New York, Miami)',
        'America/Chicago' => 'Central (Chicago, Houston)',
        'America/Denver' => 'Mountain (Denver)',
        'America/Phoenix' => 'Arizona (Phoenix)',
        'America/Los_Angeles' => 'Pacific (Los Angeles)',
        'America/Anchorage' => 'Alaska',
        'Pacific/Honolulu' => 'Hawaii',
        'America/Puerto_Rico' => 'Puerto Rico',
        'America/Mexico_City' => 'México (CDMX)',
        'America/Bogota' => 'Colombia (Bogotá)',
        'America/Caracas' => 'Venezuela (Caracas)',
        'America/Lima' => 'Perú (Lima)',
        'America/Santiago' => 'Chile (Santiago)',
        'America/Argentina/Buenos_Aires' => 'Argentina (Buenos Aires)',
        'America/Sao_Paulo' => 'Brasil (São Paulo)',
        'Europe/Madrid' => 'España (Madrid)',
    ];

    public function options(): array
    {
        $options = [];

        foreach (self::PREFERRED as $zone => $label) {
            $options[] = ['title' => $label.' — '.$zone, 'value' => $zone];
        }

        foreach (\DateTimeZone::listIdentifiers() as $zone) {
            if (! isset(self::PREFERRED[$zone])) {
                $options[] = ['title' => $zone, 'value' => $zone];
            }
        }

        return $options;
    }

    public static function isValid(?string $zone): bool
    {
        return $zone && in_array($zone, \DateTimeZone::listIdentifiers(), true);
    }
}
