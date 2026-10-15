<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\InsuranceCommission;
use Webkul\Lead\Models\InsurancePolicy;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadConsent;
use Webkul\Lead\Models\LeadDmiDocument;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;
use Webkul\Lead\Services\AgencyOverrideService;
use Webkul\Quote\Models\Quote;
use Webkul\Teamwork\Models\StageMilestone;
use Webkul\Teamwork\Models\StagePlaybook;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Http::preventStrayRequests();
});

function policyConversionScenario(bool $withWonStage = true): array
{
    $admin = getDefaultAdmin();
    $pipeline = Pipeline::create(['name' => 'Isolated ACA conversion', 'is_default' => false]);
    $initialStage = Stage::create([
        'name' => 'Application', 'code' => 'application', 'sort_order' => 1, 'lead_pipeline_id' => $pipeline->id,
    ]);
    $wonStage = $withWonStage ? Stage::create([
        'name' => 'Won', 'code' => 'won', 'sort_order' => 2, 'lead_pipeline_id' => $pipeline->id,
    ]) : null;
    $person = Person::create(['name' => 'Synthetic ACA Applicant']);
    $lead = Lead::create([
        'title' => 'Synthetic ACA application', 'person_id' => $person->id, 'user_id' => $admin->id,
        'lead_pipeline_id' => $pipeline->id, 'lead_pipeline_stage_id' => $initialStage->id,
    ]);
    $quote = Quote::create([
        'subject' => 'Synthetic health quote', 'person_id' => $person->id, 'user_id' => $admin->id,
        'carrier_name' => 'Synthetic Carrier', 'plan_name' => 'Synthetic Silver', 'metal_tier' => 'silver',
        'gross_premium' => 500, 'aptc_subsidy' => 450, 'net_premium' => 50,
        'sub_total' => 500, 'discount_amount' => 450, 'grand_total' => 50, 'quote_status' => 'presented',
    ]);
    $quote->leads()->attach($lead->id);

    return compact('admin', 'pipeline', 'initialStage', 'wonStage', 'person', 'lead', 'quote');
}

it('converts the quote into pending coverage in the correct pipeline won stage', function () {
    extract(policyConversionScenario());

    $this->actingAs($admin)->postJson(route('admin.quotes.convert_to_policy', $quote->id))
        ->assertOk()->assertJsonPath('status', true)->assertJsonPath('policy.status', 'binder_pending');

    expect($quote->fresh()->quote_status)->toBe('bound');
    expect($lead->fresh()->lead_pipeline_stage_id)->toBe($wonStage->id);
    expect($lead->fresh()->lead_pipeline_id)->toBe($pipeline->id);
    expect(InsurancePolicy::where('quote_id', $quote->id)->count())->toBe(1);
    expect(InsuranceCommission::where('quote_id', $quote->id)->count())->toBe(1);
});

it('rolls back quote policy commission and stage when override calculation fails', function () {
    extract(policyConversionScenario());
    $this->mock(AgencyOverrideService::class)->shouldReceive('distributeOverridesForPolicy')
        ->once()->andThrow(new RuntimeException('Synthetic override failure'));
    $this->withoutExceptionHandling();

    expect(fn () => $this->actingAs($admin)->postJson(route('admin.quotes.convert_to_policy', $quote->id)))
        ->toThrow(RuntimeException::class, 'Synthetic override failure');

    expect($quote->fresh()->quote_status)->toBe('presented');
    expect($lead->fresh()->lead_pipeline_stage_id)->toBe($initialStage->id);
    expect(InsurancePolicy::where('quote_id', $quote->id)->exists())->toBeFalse();
    expect(InsuranceCommission::where('quote_id', $quote->id)->exists())->toBeFalse();
});

it('rejects a pipeline without a won stage without partially creating records', function () {
    extract(policyConversionScenario(false));

    $this->actingAs($admin)->postJson(route('admin.quotes.convert_to_policy', $quote->id))
        ->assertUnprocessable()->assertJsonValidationErrors('lead_pipeline_stage_id');

    expect($quote->fresh()->quote_status)->toBe('presented');
    expect(InsurancePolicy::where('quote_id', $quote->id)->exists())->toBeFalse();
    expect(InsuranceCommission::where('quote_id', $quote->id)->exists())->toBeFalse();
});

it('keeps paid coverage and creates no duplicate commissions or history on retry', function () {
    extract(policyConversionScenario());
    $url = route('admin.quotes.convert_to_policy', $quote->id);
    $this->actingAs($admin)->postJson($url)->assertOk();
    $policy = InsurancePolicy::where('quote_id', $quote->id)->firstOrFail();
    $policy->recordBinderPayment('SYNTHETIC-PAID', 'carrier_portal');
    $historyCount = $policy->coverageHistories()->count();

    $this->postJson($url)->assertOk()->assertJsonPath('policy.status', 'active');

    expect($policy->fresh()->binder_confirmation_number)->toBe('SYNTHETIC-PAID');
    expect($policy->fresh()->binder_payment_status)->toBe('paid');
    expect($policy->coverageHistories()->count())->toBe($historyCount);
    expect(InsurancePolicy::where('quote_id', $quote->id)->count())->toBe(1);
    expect(InsuranceCommission::where('quote_id', $quote->id)->count())->toBe(1);
});

it('applies blocking pipeline milestones to quote conversion too', function () {
    extract(policyConversionScenario());
    StagePlaybook::create(['lead_pipeline_stage_id' => $initialStage->id, 'gate' => 'block']);
    StageMilestone::create([
        'lead_pipeline_stage_id' => $initialStage->id, 'position' => 1,
        'name' => 'Signed consent', 'check' => 'cms_consent', 'is_required' => true,
    ]);

    $this->actingAs($admin)->postJson(route('admin.quotes.convert_to_policy', $quote->id))
        ->assertUnprocessable()->assertJsonValidationErrors('lead_pipeline_stage_id');

    expect($quote->fresh()->quote_status)->toBe('presented');
    expect(InsurancePolicy::where('quote_id', $quote->id)->exists())->toBeFalse();
    expect(InsuranceCommission::where('quote_id', $quote->id)->exists())->toBeFalse();
});

it('preserves the household signed consent documents and coverage history through an ACA renewal', function () {
    extract(policyConversionScenario());
    Storage::fake('public');
    $this->actingAs($admin);
    StagePlaybook::create(['lead_pipeline_stage_id' => $initialStage->id, 'gate' => 'block']);
    StageMilestone::create([
        'lead_pipeline_stage_id' => $initialStage->id, 'position' => 1,
        'name' => 'Signed consent', 'check' => 'cms_consent', 'is_required' => true,
    ]);

    $this->postJson(route('admin.leads.household.store', $lead->id), [
        'name' => 'Synthetic Dependent', 'relationship' => 'child',
        'date_of_birth' => '2015-05-10', 'is_applying_coverage' => true,
    ])->assertOk();

    $this->getJson(route('admin.leads.consent.get', $lead->id))->assertOk();
    $consent = LeadConsent::where('lead_id', $lead->id)->firstOrFail();
    $signature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    $this->postJson(route('consent.sign', $consent->token), ['signature' => $signature])
        ->assertOk()->assertJsonPath('success', true);
    $version = $consent->versions()->firstOrFail();
    expect($version->verifyIntegrity())->toBeTrue();

    $this->postJson(route('admin.leads.dmi.store', $lead->id), [
        'doc_type' => 'income', 'title' => 'Synthetic income evidence',
        'deadline_date' => now()->addDays(30)->toDateString(),
        'file' => UploadedFile::fake()->create('synthetic-income.pdf', 10, 'application/pdf'),
    ])->assertOk();
    $document = LeadDmiDocument::where('lead_id', $lead->id)->firstOrFail();
    Storage::disk('public')->assertExists($document->file_path);

    $this->postJson(route('admin.quotes.convert_to_policy', $quote->id))
        ->assertOk()->assertJsonPath('policy.status', 'binder_pending');
    $policy = InsurancePolicy::where('quote_id', $quote->id)->firstOrFail();
    $this->postJson(route('admin.policies.record_binder_payment', $policy->id), [
        'confirmation_number' => 'SYNTHETIC-CARRIER-CONFIRMATION', 'payment_method' => 'carrier_portal',
    ])->assertOk();
    expect($policy->fresh()->status)->toBe('active');
    $priorHistory = $policy->coverageHistories()->pluck('id')->all();
    $renewalYear = $policy->effective_date->year + 1;

    $response = $this->postJson(route('admin.policies.process_renewal', $policy->id), [
        'plan_year' => $renewalYear, 'carrier_name' => 'Synthetic Renewal Carrier',
        'plan_name' => 'Synthetic Renewal Silver', 'gross_premium' => 550,
        'aptc_subsidy' => 480, 'net_premium' => 70,
    ])->assertOk()->assertJsonPath('policy.status', 'binder_pending');
    $renewal = InsurancePolicy::findOrFail($response->json('policy.id'));

    expect($renewal->prior_policy_id)->toBe($policy->id);
    expect($renewal->lead_id)->toBe($lead->id);
    expect($renewal->person_id)->toBe($person->id);
    expect($renewal->plan_year)->toBe($renewalYear);
    expect($policy->fresh()->status)->toBe('renewed');
    expect($policy->coverageHistories()->whereIn('id', $priorHistory)->count())->toBe(count($priorHistory));
    expect($lead->householdMembers()->count())->toBe(1);
    expect($document->fresh()->person_id)->toBe($person->id);
    expect($consent->fresh()->status)->toBe('signed');
    expect($version->fresh()->verifyIntegrity())->toBeTrue();
    Storage::disk('public')->assertExists($document->file_path);
    Http::assertNothingSent();
});

it('preserves route permissions when using the insurance controller extension', function () {
    extract(policyConversionScenario());
    $role = Role::create(['name' => 'No quote access', 'permission_type' => 'custom', 'permissions' => ['dashboard']]);
    $restricted = User::create([
        'name' => 'Synthetic Restricted User', 'email' => 'restricted-conversion@example.invalid',
        'password' => bcrypt('synthetic-password'), 'role_id' => $role->id, 'status' => 1,
        'view_permission' => 'individual',
    ]);

    $this->actingAs($restricted)->postJson(route('admin.quotes.convert_to_policy', $quote->id))
        ->assertUnauthorized();

    expect($quote->fresh()->quote_status)->toBe('presented');
    expect(InsurancePolicy::where('quote_id', $quote->id)->exists())->toBeFalse();
    expect(InsuranceCommission::where('quote_id', $quote->id)->exists())->toBeFalse();
});
