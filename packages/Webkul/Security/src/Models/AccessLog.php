<?php

namespace Webkul\Security\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\User\Models\UserProxy;

class AccessLog extends Model
{
    const EVENT_LOGIN = 'login';

    const EVENT_LOGOUT = 'logout';

    const EVENT_FAILED = 'failed';

    const EVENT_BLOCKED_IP = 'blocked_ip';

    const EVENT_MFA_FAILED = 'mfa_failed';

    const EVENT_MFA_ENABLED = 'mfa_enabled';

    const EVENT_MFA_DISABLED = 'mfa_disabled';

    const EVENT_MFA_RESET = 'mfa_reset';

    /**
     * Every event, in display order.
     */
    const EVENTS = [
        self::EVENT_LOGIN,
        self::EVENT_LOGOUT,
        self::EVENT_FAILED,
        self::EVENT_BLOCKED_IP,
        self::EVENT_MFA_FAILED,
        self::EVENT_MFA_ENABLED,
        self::EVENT_MFA_DISABLED,
        self::EVENT_MFA_RESET,
    ];

    const UPDATED_AT = null;

    protected $table = 'user_access_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'email',
        'event',
        'ip_address',
        'user_agent',
    ];

    /**
     * Get the user the entry belongs to.
     */
    public function user()
    {
        return $this->belongsTo(UserProxy::modelClass());
    }
}
