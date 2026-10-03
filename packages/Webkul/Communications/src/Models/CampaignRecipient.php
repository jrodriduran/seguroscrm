<?php

namespace Webkul\Communications\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignRecipient extends Model
{
    public $timestamps = false;

    protected $table = 'communication_campaign_recipients';

    protected $fillable = ['campaign_id', 'person_id', 'status', 'channel', 'detail', 'sent_at'];

    protected $casts = ['sent_at' => 'datetime'];
}
