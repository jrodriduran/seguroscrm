<?php

namespace Webkul\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Lead\Contracts\PolicyServiceCaseComment as PolicyServiceCaseCommentContract;
use Webkul\User\Models\UserProxy;

class PolicyServiceCaseComment extends Model implements PolicyServiceCaseCommentContract
{
    protected $table = 'policy_service_case_comments';

    protected $fillable = [
        'service_case_id',
        'user_id',
        'comment',
        'is_customer_visible',
    ];

    protected $casts = [
        'is_customer_visible' => 'boolean',
    ];

    /**
     * Parent service case relationship.
     */
    public function serviceCase(): BelongsTo
    {
        return $this->belongsTo(PolicyServiceCaseProxy::modelClass(), 'service_case_id');
    }

    /**
     * Comment author relationship.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass(), 'user_id');
    }
}
