<?php

namespace Webkul\Communications\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Communications\Services\BirthdayRecipients;
use Webkul\Teamwork\Http\Middleware\ApplyTimezone;

class BirthdayController extends Controller
{
    public function index(Request $request, BirthdayRecipients $birthdays): View
    {
        $data = $request->validate(['date' => 'nullable|date_format:Y-m-d']);
        $zone = ApplyTimezone::resolve();
        $day = isset($data['date']) ? Carbon::createFromFormat('!Y-m-d', $data['date'], $zone) : now($zone)->startOfDay();

        $contacts = $birthdays->query($day, bouncer()->getAuthorizedUserIds())
            ->leftJoin('users', 'users.id', '=', 'persons.user_id')
            ->select('persons.id', 'persons.name', 'persons.contact_numbers', 'users.name as owner_name')
            ->selectSub(DB::table('contact_consents')->select('status')
                ->whereColumn('person_id', 'persons.id')->where('channel', 'whatsapp')->latest('id')->limit(1), 'wa_consent')
            ->orderBy('persons.name')->orderBy('persons.id')
            ->paginate(50)->withQueryString();

        $contacts->through(function ($contact) use ($birthdays) {
            $contact->phone_suffix = $birthdays->phoneSuffix($contact->contact_numbers);
            $contact->review_status = ! $contact->phone_suffix ? 'missing-phone'
                : ($contact->wa_consent !== 'granted' ? 'missing-consent' : 'review');
            unset($contact->contact_numbers);

            return $contact;
        });

        return view('communications::birthdays.index', compact('contacts', 'day', 'zone'));
    }
}
