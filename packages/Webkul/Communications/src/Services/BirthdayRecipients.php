<?php

namespace Webkul\Communications\Services;

use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * One source for the daily birthday list and scheduler. Uses saved contact
 * birth dates; never invents one for a missing or future value.
 */
class BirthdayRecipients
{
    public function query(CarbonInterface $day, ?array $ownerIds = null): Builder
    {
        $query = DB::table('persons')
            ->join('attribute_values as birthday', 'birthday.entity_id', '=', 'persons.id')
            ->join('attributes as birthday_attribute', 'birthday_attribute.id', '=', 'birthday.attribute_id')
            ->where('birthday_attribute.entity_type', 'persons')->where('birthday_attribute.code', 'dob')
            ->where('birthday.entity_type', 'persons')
            ->whereMonth('birthday.date_value', $day->month)->whereDay('birthday.date_value', $day->day)
            ->whereDate('birthday.date_value', '<=', $day->toDateString());

        // null means unrestricted; an empty scope must never disclose all contacts.
        if ($ownerIds !== null) {
            $query->whereIn('persons.user_id', $ownerIds);
        }

        return $query;
    }

    public function phoneSuffix(?string $json): ?string
    {
        foreach (json_decode((string) $json, true) ?: [] as $number) {
            if (preg_match('/[a-z]/i', (string) ($number['value'] ?? ''))) {
                continue;
            }
            $digits = preg_replace('/\D/', '', (string) ($number['value'] ?? ''));
            if (strlen($digits) >= 8 && strlen($digits) <= 15 && $digits[0] !== '0') {
                return '••••'.substr($digits, -4);
            }
        }

        return null;
    }
}
