<?php

namespace Webkul\Communications\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Webkul\Communications\Services\ZipLookup;

/**
 * Starts each client's mailing address from the ZIP code on their latest
 * lead (city and state looked up), for clients who have no address yet.
 */
class BackfillAddresses extends Command
{
    protected $signature = 'communications:backfill-addresses {--dry-run : Show what would change without saving}';

    protected $description = 'Fill client mailing addresses (ZIP, city, state) from their leads';

    public function handle(ZipLookup $lookup): int
    {
        $addressId = DB::table('attributes')->where('entity_type', 'persons')->where('code', 'address')->value('id');
        $zipId = DB::table('attributes')->where('entity_type', 'leads')->where('code', 'zip_code')->value('id');

        if (! $addressId || ! $zipId) {
            $this->error('The address or ZIP attribute is missing. Run the migrations first.');

            return self::FAILURE;
        }

        $withAddress = DB::table('attribute_values')->where('attribute_id', $addressId)->pluck('entity_id');

        $leads = DB::table('leads')
            ->join('attribute_values as zip', fn ($join) => $join->on('zip.entity_id', '=', 'leads.id')->where('zip.attribute_id', $zipId))
            ->whereNotNull('leads.person_id')
            ->whereNotIn('leads.person_id', $withAddress)
            ->orderByDesc('leads.id')
            ->get(['leads.person_id', 'leads.state_code', 'zip.text_value as zip'])
            ->unique('person_id');

        $filled = 0;

        foreach ($leads as $lead) {
            $place = $lookup->lookup((string) $lead->zip);

            if (! $place && ! $lead->state_code) {
                continue;
            }

            $address = [
                'address' => '',
                'city' => $place['city'] ?? '',
                'state' => $place['state'] ?? $lead->state_code,
                'postcode' => $place['zip'] ?? $lead->zip,
                'country' => 'US',
            ];

            $this->line("#{$lead->person_id}: {$address['city']}, {$address['state']} {$address['postcode']}");

            if (! $this->option('dry-run')) {
                DB::table('attribute_values')->insert([
                    'entity_type' => 'persons',
                    'entity_id' => $lead->person_id,
                    'attribute_id' => $addressId,
                    'json_value' => json_encode($address),
                ]);
            }

            $filled++;
        }

        $this->info(($this->option('dry-run') ? 'Would fill ' : 'Filled ').$filled.' address(es).');

        return self::SUCCESS;
    }
}
