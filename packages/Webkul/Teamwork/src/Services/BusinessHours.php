<?php

namespace Webkul\Teamwork\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Working time between two moments, using the agency's business hours
 * (Configuration > General > Teamwork): timezone, working days and the
 * start/end of the working day.
 */
class BusinessHours
{
    /**
     * Longest span walked day by day; anything older is extrapolated by weeks.
     */
    const MAX_DAYS_WALKED = 120;

    protected string $timezone;

    /**
     * ISO weekdays that are working days (1 = Monday … 7 = Sunday).
     */
    protected array $workDays;

    protected int $startMinutes;

    protected int $endMinutes;

    public function __construct()
    {
        // The agency time zone (Configuration > General > Time zone), applied per request by ApplyTimezone.
        $this->timezone = config('app.timezone') ?: 'America/New_York';

        try {
            new \DateTimeZone($this->timezone);
        } catch (\Exception) {
            $this->timezone = 'America/New_York';
        }

        $days = array_filter(array_map('intval', preg_split('/[\s,;]+/', (string) $this->config('work_days', '1,2,3,4,5'))));

        $this->workDays = array_values(array_intersect($days, range(1, 7))) ?: [1, 2, 3, 4, 5];

        $this->startMinutes = $this->minutes($this->config('day_start', '09:00'), 9 * 60);

        $this->endMinutes = $this->minutes($this->config('day_end', '18:00'), 18 * 60);

        if ($this->endMinutes <= $this->startMinutes) {
            [$this->startMinutes, $this->endMinutes] = [9 * 60, 18 * 60];
        }
    }

    /**
     * Length of one working day in hours.
     */
    public function hoursPerDay(): float
    {
        return ($this->endMinutes - $this->startMinutes) / 60;
    }

    /**
     * Business hours elapsed between two moments (0 if $to is before $from).
     */
    public function between(DateTimeInterface $from, DateTimeInterface $to): float
    {
        $from = CarbonImmutable::instance($from)->setTimezone($this->timezone);
        $to = CarbonImmutable::instance($to)->setTimezone($this->timezone);

        if ($to <= $from) {
            return 0.0;
        }

        $extraMinutes = 0;

        // Jump over whole weeks for very old records, keeping the same weekday.
        $days = (int) $from->startOfDay()->diffInDays($to->startOfDay());

        if ($days > self::MAX_DAYS_WALKED) {
            $weeks = intdiv($days - self::MAX_DAYS_WALKED, 7) + 1;

            $extraMinutes = $weeks * count($this->workDays) * ($this->endMinutes - $this->startMinutes);

            $from = $from->addWeeks($weeks);
        }

        $minutes = 0;

        for ($day = $from->startOfDay(); $day <= $to; $day = $day->addDay()) {
            if (! in_array($day->dayOfWeekIso, $this->workDays, true)) {
                continue;
            }

            $open = $day->addMinutes($this->startMinutes);
            $close = $day->addMinutes($this->endMinutes);

            $start = $from > $open ? $from : $open;
            $end = $to < $close ? $to : $close;

            if ($end > $start) {
                $minutes += $start->diffInMinutes($end);
            }
        }

        return round(($minutes + $extraMinutes) / 60, 1);
    }

    /**
     * End of the working day that is $days working days from now (0 = today).
     */
    public function addWorkingDays(int $days): CarbonImmutable
    {
        $date = CarbonImmutable::now($this->timezone);

        while (! in_array($date->dayOfWeekIso, $this->workDays, true)) {
            $date = $date->addDay();
        }

        for ($added = 0; $added < $days;) {
            $date = $date->addDay();

            if (in_array($date->dayOfWeekIso, $this->workDays, true)) {
                $added++;
            }
        }

        return $date->startOfDay()->addMinutes($this->endMinutes)->setTimezone(config('app.timezone'));
    }

    /**
     * Human label: "5 h", "2 d 3 h" (days are working days).
     */
    public function format(float $hours): string
    {
        $perDay = $this->hoursPerDay();

        if ($hours < $perDay) {
            return trans('teamwork::app.time.hours', ['hours' => (int) floor($hours)]);
        }

        $days = (int) floor($hours / $perDay);
        $rest = (int) floor($hours - $days * $perDay);

        return $rest
            ? trans('teamwork::app.time.days-hours', ['days' => $days, 'hours' => $rest])
            : trans('teamwork::app.time.days', ['days' => $days]);
    }

    protected function config(string $field, $default)
    {
        $value = core()->getConfigData('general.teamwork.business_hours.'.$field);

        return $value === null || $value === '' ? $default : $value;
    }

    protected function minutes($time, int $default): int
    {
        if (! preg_match('/^(\d{1,2}):(\d{2})$/', trim((string) $time), $matches)) {
            return $default;
        }

        return min(24 * 60, ((int) $matches[1]) * 60 + (int) $matches[2]);
    }
}
