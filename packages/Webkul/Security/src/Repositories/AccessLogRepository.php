<?php

namespace Webkul\Security\Repositories;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Webkul\Security\Models\AccessLog;

class AccessLogRepository
{
    /**
     * Record an access event for the current request.
     */
    public function record(string $event, ?int $userId = null, ?string $email = null, ?Request $request = null): AccessLog
    {
        $request ??= request();

        return AccessLog::create([
            'user_id' => $userId,
            'email' => $email ? Str::limit($email, 250, '') : null,
            'event' => $event,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ]);
    }
}
