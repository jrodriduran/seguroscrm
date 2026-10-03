<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Only IMAP polls a mailbox; Sendgrid delivers inbound mail through its webhook
// (bulk processing is not supported there and failed on every run).
Schedule::command('inbound-emails:process')
    ->everyFiveMinutes()
    ->when(fn () => config('mail-receiver.default') === 'webklex-imap');
