<?php

namespace Webkul\Platform\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Webkul\Platform\Services\PlatformState;
use Webkul\Platform\Services\SupportAccess;

/**
 * API for the SaaS operator panel. Every request must be signed:
 *
 *   X-Platform-Timestamp: unix seconds
 *   X-Platform-Signature: hex hmac_sha256("{timestamp}.{METHOD}.{path}.{body}", PLATFORM_SECRET)
 */
class PlatformApiController extends Controller
{
    public function __construct(protected PlatformState $state, protected SupportAccess $support) {}

    public function health(Request $request): JsonResponse
    {
        $this->verify($request);

        return response()->json($this->support->health($this->state));
    }

    public function status(Request $request): JsonResponse
    {
        $this->verify($request);

        $data = $request->validate([
            'status' => ['required', Rule::in(PlatformState::STATUSES)],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $this->state->set($data['status'], $data['message'] ?? null);

        return response()->json(['status' => $this->state->status()]);
    }

    public function supportLink(Request $request): JsonResponse
    {
        $this->verify($request);

        $user = $this->support->owner($request->input('email'));
        abort_unless($user, 404);

        return response()->json([
            'url' => $this->support->createLink($user, $request->input('reason')),
            'user' => $user->email,
            'expires_in' => (int) config('platform.support_link_ttl', 60),
        ]);
    }

    public function resetOwner(Request $request): JsonResponse
    {
        $this->verify($request);

        $user = $this->support->owner($request->input('email'));
        abort_unless($user, 404);

        return response()->json([
            'user' => $user->email,
            'temporary_password' => $this->support->resetPassword($user, $request->boolean('disable_two_factor')),
        ]);
    }

    protected function verify(Request $request): void
    {
        $secret = (string) config('platform.secret');
        $timestamp = (int) $request->header('X-Platform-Timestamp');
        $signature = (string) $request->header('X-Platform-Signature');

        abort_if($secret === '', 404);
        abort_if(abs(time() - $timestamp) > (int) config('platform.signature_ttl', 300), 401, 'Expired signature');

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->method().'.'.$request->path().'.'.$request->getContent(), $secret);

        abort_unless(hash_equals($expected, $signature), 401, 'Invalid signature');
    }
}
