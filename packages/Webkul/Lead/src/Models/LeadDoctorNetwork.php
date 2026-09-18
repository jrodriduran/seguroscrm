<?php

namespace Webkul\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Lead\Contracts\LeadDoctorNetwork as LeadDoctorNetworkContract;

class LeadDoctorNetwork extends Model implements LeadDoctorNetworkContract
{
    protected $table = 'lead_doctor_networks';

    protected $fillable = [
        'lead_id',
        'doctor_name',
        'specialty',
        'npi_number',
        'clinic_or_hospital',
        'address_city_state',
        'phone',
        'carrier_network_status',
        'is_primary_physician',
        'notes',
    ];

    protected $casts = [
        'is_primary_physician' => 'boolean',
        'carrier_network_status' => 'array',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(LeadProxy::modelClass());
    }

    /**
     * Check if doctor is In-Network, Out-of-Network, or Unknown for a given carrier.
     */
    public function getStatusForCarrier(string $carrierName): string
    {
        $statuses = $this->carrier_network_status ?: [];

        foreach ($statuses as $carrier => $status) {
            if (stripos($carrier, $carrierName) !== false || stripos($carrierName, $carrier) !== false) {
                return $status;
            }
        }

        return 'Unknown';
    }
}
