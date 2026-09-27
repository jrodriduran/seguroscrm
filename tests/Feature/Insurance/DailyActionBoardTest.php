<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\InsurancePolicy;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadDmiDocument;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\SepQualification;
use Webkul\Lead\Models\Stage;
use Webkul\Lead\Services\DailyActionBoardService;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

it('compiles critical urgent actions across binder, dmi, and grace periods in the action board', function () {
    $admin = User::first();
    $pipeline = Pipeline::first() ?: Pipeline::create(['name' => 'General Pipeline', 'is_default' => 1]);
    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create(['name' => 'New', 'code' => 'new', 'lead_pipeline_id' => $pipeline->id]);

    $person = Person::create([
        'name' => 'Elena Action Client',
        'emails' => [['value' => 'elena.action.'.uniqid().'@test.com', 'label' => 'work']],
        'contact_numbers' => [['value' => '3055554321', 'label' => 'mobile']],
    ]);

    $lead = Lead::create([
        'title' => 'Elena Action Lead',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person->id,
        'user_id' => $admin->id,
    ]);

    // 1. Binder Pending Policy with due date in 2 days
    $binderPolicy = InsurancePolicy::create([
        'policy_number' => 'POL-BINDER-'.uniqid(),
        'carrier_name' => 'Florida Blue',
        'plan_name' => 'BlueOptions Silver',
        'status' => 'binder_pending',
        'binder_payment_status' => 'pending',
        'binder_amount' => 45.00,
        'net_premium' => 45.00,
        'binder_due_date' => Carbon::today()->addDays(2)->toDateString(),
        'lead_id' => $lead->id,
        'user_id' => $admin->id,
    ]);

    // 2. DMI Inconsistency with 7 days remaining
    $dmiDoc = LeadDmiDocument::create([
        'lead_id' => $lead->id,
        'dmi_type' => 'income',
        'title' => 'W-2 Inconsistency Verification',
        'days_remaining' => 7,
        'status' => 'pending_upload',
        'due_date' => Carbon::today()->addDays(7),
        'urgency_level' => 'critical',
    ]);

    // 3. Grace Period Month 2-3 Policy
    $gracePolicy = InsurancePolicy::create([
        'policy_number' => 'POL-GRACE-'.uniqid(),
        'carrier_name' => 'Ambetter Health',
        'plan_name' => 'Ambetter Balanced Care',
        'status' => 'grace_period_2_3',
        'grace_period_days' => 45,
        'premium_amount' => 60.00,
        'paid_to_date' => Carbon::today()->subDays(45)->toDateString(),
        'lead_id' => $lead->id,
        'user_id' => $admin->id,
    ]);

    // 4. SEP Expiring in 5 days
    $sep = SepQualification::create([
        'lead_id' => $lead->id,
        'sep_type' => 'loss_of_coverage',
        'event_date' => Carbon::today()->subDays(55)->toDateString(), // 60 - 55 = 5 days left
        'status' => 'active',
    ]);

    $service = app(DailyActionBoardService::class);
    $data = $service->getActionBoardData($admin->id);

    expect($data['metrics']['total_urgent_actions'])->toBeGreaterThanOrEqual(4);
    expect($data['metrics']['binder_pending_count'])->toBeGreaterThanOrEqual(1);
    expect($data['metrics']['dmi_critical_count'])->toBeGreaterThanOrEqual(1);
    expect($data['metrics']['grace_period_count'])->toBeGreaterThanOrEqual(1);
    expect($data['metrics']['sep_expiring_count'])->toBeGreaterThanOrEqual(1);
    expect($data['metrics']['revenue_at_risk_amount'])->toBeGreaterThanOrEqual(105.00);

    // Verify binder policy item attributes
    $binderItem = collect($data['binder_pending'])->firstWhere('id', $binderPolicy->id);
    expect($binderItem)->not->toBeNull();
    expect($binderItem['urgency'])->toEqual('critical');
    expect($binderItem['client_name'])->toEqual('Elena Action Client');
});

it('renders the daily action board dashboard and returns json data endpoint', function () {
    $admin = User::first();
    $this->actingAs($admin);

    $htmlResponse = $this->get(route('admin.insurance.action_board.index'));
    $htmlResponse->assertOk();
    $htmlResponse->assertSee('Daily Action Board');

    $jsonResponse = $this->getJson(route('admin.insurance.action_board.data'));
    $jsonResponse->assertOk();
    $jsonResponse->assertJsonStructure([
        'success',
        'data' => [
            'metrics' => [
                'total_urgent_actions',
                'binder_pending_count',
                'dmi_critical_count',
                'grace_period_count',
                'sep_expiring_count',
                'revenue_at_risk_amount',
            ],
            'binder_pending',
            'dmi_critical',
            'grace_periods',
            'sep_expiring',
        ],
    ]);
});
