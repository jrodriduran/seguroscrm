<?php

namespace Webkul\Teamwork\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Teamwork\Services\Entities;
use Webkul\Teamwork\Services\Followers;

class FollowerController extends Controller
{
    public function __construct(
        protected Entities $entities,
        protected Followers $followers,
    ) {}

    /**
     * Follow / unfollow a record.
     */
    public function toggle(string $type, int $id): RedirectResponse
    {
        validator(['type' => $type], ['type' => [Rule::in(array_keys(Entities::TYPES))]])->validate();

        abort_unless($this->entities->describe($type, $id), 404);

        $userId = auth()->guard('user')->id();

        if ($this->followers->isFollowing($userId, $type, $id)) {
            $this->followers->unfollow($userId, $type, $id);

            session()->flash('success', trans('teamwork::app.followers.unfollowed'));
        } else {
            $this->followers->follow($userId, $type, $id);

            session()->flash('success', trans('teamwork::app.followers.followed'));
        }

        return back();
    }
}
