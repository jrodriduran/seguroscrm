<?php

namespace Webkul\Communications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateContent extends Model
{
    protected $table = 'communication_template_contents';

    protected $fillable = ['template_id', 'channel', 'locale', 'subject', 'body'];

    public function template(): BelongsTo
    {
        return $this->belongsTo(CommunicationTemplate::class, 'template_id');
    }
}
