<?php

namespace Webkul\Security\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Webkul\Security\Models\AccessLog;
use Webkul\Security\Repositories\AccessLogRepository;

class RecordAccessEvent
{
    /**
     * Create a new listener instance.
     */
    public function __construct(protected AccessLogRepository $accessLogRepository) {}

    /**
     * Successful admin sign-in.
     */
    public function handleLogin(Login $event): void
    {
        if ($event->guard !== 'user') {
            return;
        }

        $this->accessLogRepository->record(AccessLog::EVENT_LOGIN, $event->user->id, $event->user->email);
    }

    /**
     * Admin sign-out.
     */
    public function handleLogout(Logout $event): void
    {
        if ($event->guard !== 'user' || ! $event->user) {
            return;
        }

        $this->accessLogRepository->record(AccessLog::EVENT_LOGOUT, $event->user->id, $event->user->email);
    }

    /**
     * Failed admin sign-in attempt (wrong password or unknown email).
     */
    public function handleFailed(Failed $event): void
    {
        if ($event->guard !== 'user') {
            return;
        }

        $this->accessLogRepository->record(
            AccessLog::EVENT_FAILED,
            $event->user?->id,
            $event->credentials['email'] ?? null,
        );
    }
}
