<?php

namespace Webkul\Communications\Services;

use Carbon\Carbon;

/**
 * Special dates for greeting campaigns, with the list and template each
 * one suggests. Dates follow the US calendar (the agencies' clients).
 */
class Occasions
{
    /**
     * code => [audience code, template code]
     */
    public const ALL = [
        'mothers_day' => ['mothers', 'mothers_day'],
        'fathers_day' => ['fathers', 'fathers_day'],
        'thanksgiving' => ['active_clients', 'thanksgiving'],
        'christmas' => ['active_clients', 'christmas'],
        'new_year' => ['active_clients', 'new_year'],
    ];

    /**
     * Next date of each occasion, soonest first.
     *
     * @return array<int, array{code: string, date: Carbon, audience: string, template: string}>
     */
    public function upcoming(?Carbon $from = null): array
    {
        $from = ($from ?? now())->copy()->startOfDay();

        return collect(self::ALL)
            ->map(function ($defaults, $code) use ($from) {
                $date = $this->dateFor($code, $from->year);

                if ($date->lt($from)) {
                    $date = $this->dateFor($code, $from->year + 1);
                }

                return ['code' => $code, 'date' => $date, 'audience' => $defaults[0], 'template' => $defaults[1]];
            })
            ->sortBy(fn ($occasion) => $occasion['date']->timestamp)
            ->values()
            ->all();
    }

    public function dateFor(string $code, int $year): Carbon
    {
        return match ($code) {
            // Second Sunday of May.
            'mothers_day' => Carbon::create($year, 5, 1)->subDay()->next(Carbon::SUNDAY)->addWeek()->startOfDay(),
            // Third Sunday of June.
            'fathers_day' => Carbon::create($year, 6, 1)->subDay()->next(Carbon::SUNDAY)->addWeeks(2)->startOfDay(),
            // Fourth Thursday of November.
            'thanksgiving' => Carbon::create($year, 11, 1)->subDay()->next(Carbon::THURSDAY)->addWeeks(3)->startOfDay(),
            'christmas' => Carbon::create($year, 12, 24)->startOfDay(),
            'new_year' => Carbon::create($year, 12, 31)->startOfDay(),
        };
    }
}
