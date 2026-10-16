<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Communications\Models\ContactConsent;
use Webkul\Communications\Models\Sequence;
use Webkul\Communications\Models\Trigger;
use Webkul\Communications\Services\BirthdayRecipients;
use Webkul\Communications\Services\CampaignRunner;
use Webkul\Communications\Services\SequenceEngine;
use Webkul\Contact\Models\Person;
use Webkul\Teamwork\Http\Middleware\ApplyTimezone;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Http::preventStrayRequests();
});

function birthdayAgent(array $permissions = ['communication_birthdays']): User
{
    $role = Role::create(['name' => 'Synthetic birthday role', 'permission_type' => 'custom', 'permissions' => $permissions]);

    return User::create([
        'name' => 'Synthetic birthday agent', 'email' => Str::uuid().'@example.invalid',
        'password' => bcrypt('synthetic-birthday-test'), 'role_id' => $role->id, 'status' => 1, 'view_permission' => 'individual',
    ]);
}

function birthdayContact(User $owner, ?string $date = '2000-10-16', array $phones = [['value' => '+1 202 555 0146', 'label' => 'work']]): Person
{
    $person = Person::create(['name' => 'Synthetic birthday '.Str::random(6), 'user_id' => $owner->id, 'emails' => [], 'contact_numbers' => $phones]);
    if ($date) {
        $attribute = Attribute::firstOrCreate(['code' => 'dob', 'entity_type' => 'persons'], ['name' => 'Date of birth', 'type' => 'date']);
        DB::table('attribute_values')->insert([
            'attribute_id' => $attribute->id, 'entity_id' => $person->id, 'entity_type' => 'persons', 'date_value' => $date,
        ]);
    }

    return $person;
}

it('lets a permitted agent review their own birthdays with masked contact details and no sends', function () {
    $owner = birthdayAgent();
    $person = birthdayContact($owner);
    ContactConsent::create(['person_id' => $person->id, 'channel' => 'whatsapp', 'status' => 'granted', 'source' => 'written', 'user_id' => $owner->id]);
    $response = $this->actingAs($owner)->get(route('admin.communications.birthdays.index', ['date' => '2026-10-16']));
    $response->assertOk()->assertSee($person->name)->assertSee('••••0146')->assertDontSee('+1 202 555 0146');
    $response->assertViewHas('contacts', fn ($contacts) => $contacts->sole()->review_status === 'review');
    Http::assertNothingSent();
});

it('hides other agents contacts and denies access without the birthday permission', function () {
    $owner = birthdayAgent();
    $other = birthdayAgent();
    $mine = birthdayContact($owner);
    $theirs = birthdayContact($other);
    $this->actingAs($owner)->get(route('admin.communications.birthdays.index', ['date' => '2026-10-16']))
        ->assertOk()->assertSee($mine->name)->assertDontSee($theirs->name);
    $this->actingAs(birthdayAgent(['leads']))->getJson(route('admin.communications.birthdays.index'))->assertUnauthorized();
});

it('fails closed for an explicitly empty owner scope', function () {
    birthdayContact(birthdayAgent());
    expect(app(BirthdayRecipients::class)->query(Carbon::parse('2026-10-16'), [])->count())->toBe(0);
});

it('flags missing phone or the latest revoked consent instead of claiming a contact is ready', function ($phone, $expected) {
    $owner = birthdayAgent();
    $person = birthdayContact($owner, '2000-10-16', $phone ? [['value' => '+12025550146']] : []);
    foreach (['granted', 'revoked'] as $status) {
        ContactConsent::create(['person_id' => $person->id, 'channel' => 'whatsapp', 'status' => $status, 'source' => 'written', 'user_id' => $owner->id]);
    }
    $this->actingAs($owner)->get(route('admin.communications.birthdays.index', ['date' => '2026-10-16']))
        ->assertOk()->assertViewHas('contacts', fn ($contacts) => $contacts->sole()->review_status === $expected);
})->with([[false, 'missing-phone'], [true, 'missing-consent']]);

it('does not treat an email grant as WhatsApp consent', function () {
    $owner = birthdayAgent();
    $person = birthdayContact($owner);
    ContactConsent::create(['person_id' => $person->id, 'channel' => 'email', 'status' => 'granted', 'source' => 'written', 'user_id' => $owner->id]);
    $this->actingAs($owner)->get(route('admin.communications.birthdays.index', ['date' => '2026-10-16']))
        ->assertOk()->assertViewHas('contacts', fn ($contacts) => $contacts->sole()->review_status === 'missing-consent');
});

it('excludes missing future different-day and wrong-entity birth dates', function () {
    $owner = birthdayAgent();
    $valid = birthdayContact($owner);
    birthdayContact($owner, null);
    birthdayContact($owner, '2030-10-16');
    birthdayContact($owner, '2000-10-17');
    $wrongEntity = birthdayContact($owner);
    DB::table('attribute_values')->where('entity_id', $wrongEntity->id)->where('entity_type', 'persons')->update(['entity_type' => 'leads']);
    $ids = app(BirthdayRecipients::class)->query(Carbon::parse('2026-10-16'), [$owner->id])->pluck('persons.id')->all();
    expect($ids)->toBe([$valid->id]);
});

it('uses an edited birth date and never retains the old birthday', function () {
    $owner = birthdayAgent();
    $person = birthdayContact($owner);
    DB::table('attribute_values')->where('entity_id', $person->id)->where('entity_type', 'persons')->update(['date_value' => '2000-10-17']);
    expect(app(BirthdayRecipients::class)->query(Carbon::parse('2026-10-16'), [$owner->id])->count())->toBe(0);
    expect(app(BirthdayRecipients::class)->query(Carbon::parse('2026-10-17'), [$owner->id])->count())->toBe(1);
});

it('does not move February 29 birthdays to an invented date', function () {
    $owner = birthdayAgent();
    birthdayContact($owner, '2000-02-29');
    $birthdays = app(BirthdayRecipients::class);
    expect($birthdays->query(Carbon::parse('2027-02-28'), [$owner->id])->count())->toBe(0);
    expect($birthdays->query(Carbon::parse('2027-03-01'), [$owner->id])->count())->toBe(0);
    expect($birthdays->query(Carbon::parse('2028-02-29'), [$owner->id])->count())->toBe(1);
});

it('validates the review date', function () {
    $this->actingAs(birthdayAgent())->getJson(route('admin.communications.birthdays.index', ['date' => '2026-02-31']))->assertUnprocessable();
});

it('renders resolved birthday texts in supported agency languages', function ($locale, $title) {
    $this->actingAs(birthdayAgent())->get(route('admin.communications.birthdays.index', ['date' => '2026-10-16', 'locale' => $locale]))
        ->assertOk()->assertSee($title)->assertDontSee('communications::app.birthdays');
})->with([['es', 'Cumpleaños'], ['en', 'Birthdays'], ['pt_BR', 'Aniversários']]);

it('shares the filtered recipient lookup with the scheduler without using external services', function () {
    $owner = birthdayAgent();
    $day = now(ApplyTimezone::resolve());
    $person = birthdayContact($owner, '2000-'.$day->format('m-d'));
    $future = birthdayContact($owner, '2099-'.$day->format('m-d'));
    DB::table('communication_sequences')->update(['is_active' => false]);
    $sequence = Sequence::create(['code' => 'synthetic-birthday', 'name' => 'Synthetic birthday', 'is_active' => true]);
    Trigger::create(['sequence_id' => $sequence->id, 'event' => 'birthday', 'is_active' => true]);
    $engine = Mockery::mock(SequenceEngine::class);
    $engine->shouldReceive('fire')->with('birthday', [], $person->id, null, null, 'birthday:'.$day->year)->once()->andReturn(1);
    // Existing synthetic fixtures can have birthdays too; only this test's person is required.
    $engine->shouldReceive('fire')->withArgs(fn ($event, $match, $id) => $id !== $person->id && $id !== $future->id)->andReturn(0);
    $engine->shouldReceive('runDue')->once()->andReturn(0);
    $campaigns = Mockery::mock(CampaignRunner::class);
    $campaigns->shouldReceive('runDue')->once()->andReturn(0);
    $this->app->instance(SequenceEngine::class, $engine);
    $this->app->instance(CampaignRunner::class, $campaigns);
    $this->artisan('communications:run-sequences')->assertSuccessful();
    Http::assertNothingSent();
});
