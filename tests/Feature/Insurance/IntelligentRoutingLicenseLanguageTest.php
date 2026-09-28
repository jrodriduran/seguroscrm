<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Webkul\Lead\Models\AssignmentRule;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;
use Webkul\Lead\Models\UserAgentLicense;
use Webkul\Lead\Services\AssignmentEngine;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function createAgentWithLicense(string $name, string $email, array $spokenLanguages, ?string $stateCode = null, ?array $lines = null, ?string $expiresAt = null, string $status = 'active'): User
{
    $user = User::create([
        'name' => $name,
        'email' => $email,
        'password' => Hash::make('password123'),
        'status' => 1,
        'role_id' => 1,
        'spoken_languages' => $spokenLanguages,
    ]);

    if ($stateCode) {
        UserAgentLicense::create([
            'user_id' => $user->id,
            'state_code' => $stateCode,
            'license_number' => 'LIC-'.strtoupper($stateCode).'-'.rand(1000, 9999),
            'license_type' => 'resident',
            'lines_of_authority' => $lines ?? ['health'],
            'expires_at' => $expiresAt ?? Carbon::today()->addYear()->toDateString(),
            'status' => $status,
        ]);
    }

    return $user;
}

it('routes a Texas Spanish lead exclusively to a TX-licensed bilingual agent', function () {
    $pipeline = Pipeline::first() ?: Pipeline::create(['name' => 'ACA Intake Pipeline', 'is_default' => 1]);
    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create(['name' => 'New', 'code' => 'new', 'lead_pipeline_id' => $pipeline->id]);

    // Agent 1: Texas licensed (health), bilingual [es, en]
    $agentTX = createAgentWithLicense('Carlos Texas', 'carlos.tx.'.uniqid().'@test.com', ['es', 'en'], 'TX', ['health']);

    // Agent 2: Florida licensed (health), speaks Spanish
    $agentFL = createAgentWithLicense('Maria Florida', 'maria.fl.'.uniqid().'@test.com', ['es'], 'FL', ['health']);

    // Agent 3: Texas licensed, but ONLY for 'life' (no health line of authority)
    $agentTXLifeOnly = createAgentWithLicense('Pedro Life', 'pedro.life.'.uniqid().'@test.com', ['es'], 'TX', ['life']);

    // Assignment rule targeting all 3 agents
    $rule = AssignmentRule::create([
        'lead_pipeline_id' => $pipeline->id,
        'name' => 'Intelligent ACA Rule',
        'strategy' => 'round_robin',
        'is_active' => 1,
        'agent_ids' => [$agentTX->id, $agentFL->id, $agentTXLifeOnly->id],
        'max_capacity' => 20,
    ]);

    $lead = Lead::create([
        'title' => 'Familia Hernandez - ACA Texas',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'state_code' => 'TX',
        'preferred_language' => 'es',
    ]);

    $engine = app(AssignmentEngine::class);
    $assignedId = $engine->assign($lead);

    expect($assignedId)->toEqual($agentTX->id);
    expect($lead->fresh()->assignment_failure_reason)->toBeNull();
});

it('excludes agents with expired licenses from the assignment pool', function () {
    $pipeline = Pipeline::first() ?: Pipeline::create(['name' => 'ACA Intake Pipeline', 'is_default' => 1]);
    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create(['name' => 'New', 'code' => 'new', 'lead_pipeline_id' => $pipeline->id]);

    // Agent with expired Texas license
    $expiredAgent = createAgentWithLicense(
        'Expired License Agent',
        'expired.'.uniqid().'@test.com',
        ['es'],
        'TX',
        ['health'],
        Carbon::today()->subMonths(2)->toDateString(),
        'expired'
    );

    // Agent with active Texas license
    $activeAgent = createAgentWithLicense(
        'Active License Agent',
        'active.'.uniqid().'@test.com',
        ['es'],
        'TX',
        ['health'],
        Carbon::today()->addMonths(6)->toDateString(),
        'active'
    );

    $rule = AssignmentRule::create([
        'lead_pipeline_id' => $pipeline->id,
        'name' => 'License Expiration Filter Rule',
        'strategy' => 'round_robin',
        'is_active' => 1,
        'agent_ids' => [$expiredAgent->id, $activeAgent->id],
        'max_capacity' => 10,
    ]);

    $lead = Lead::create([
        'title' => 'Cliente Austin - Lead',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'state_code' => 'TX',
        'preferred_language' => 'es',
    ]);

    $engine = app(AssignmentEngine::class);
    $assignedId = $engine->assign($lead);

    expect($assignedId)->toEqual($activeAgent->id);
});

it('does not assign lead and records audit failure reason when no agent is licensed in the state', function () {
    $pipeline = Pipeline::first() ?: Pipeline::create(['name' => 'ACA Intake Pipeline', 'is_default' => 1]);
    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create(['name' => 'New', 'code' => 'new', 'lead_pipeline_id' => $pipeline->id]);

    // Only Florida agents available
    $agentFL = createAgentWithLicense('Laura Miami', 'laura.'.uniqid().'@test.com', ['es', 'en'], 'FL', ['health']);

    $rule = AssignmentRule::create([
        'lead_pipeline_id' => $pipeline->id,
        'name' => 'Strict State Rule',
        'strategy' => 'round_robin',
        'is_active' => 1,
        'agent_ids' => [$agentFL->id],
        'max_capacity' => 10,
    ]);

    // Lead from California where no agent has license
    $lead = Lead::create([
        'title' => 'California Lead Unlicensed State',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'state_code' => 'CA',
        'preferred_language' => 'en',
    ]);

    $engine = app(AssignmentEngine::class);
    $assignedId = $engine->assign($lead);

    expect($assignedId)->toBeNull();
    expect($lead->fresh()->assignment_failure_reason)->toEqual('no_licensed_agents_in_state');
});

it('provides diagnostics and audit breakdown via auditLeadAssignment', function () {
    $pipeline = Pipeline::first() ?: Pipeline::create(['name' => 'ACA Intake Pipeline', 'is_default' => 1]);

    $agentNC = createAgentWithLicense('David NC', 'david.nc.'.uniqid().'@test.com', ['es'], 'NC', ['health']);

    $rule = AssignmentRule::create([
        'lead_pipeline_id' => $pipeline->id,
        'name' => 'Diagnostic Rule',
        'strategy' => 'least_loaded',
        'is_active' => 1,
        'agent_ids' => [$agentNC->id],
        'max_capacity' => 5,
    ]);

    $engine = app(AssignmentEngine::class);

    $audit = $engine->auditLeadAssignment([
        'lead_pipeline_id' => $pipeline->id,
        'state_code' => 'NC',
        'preferred_language' => 'es',
    ]);

    expect($audit['state_code'])->toEqual('NC');
    expect($audit['preferred_language'])->toEqual('es');
    expect($audit['licensed_pool_count'])->toEqual(1);
    expect($audit['is_routable'])->toBeTrue();
    expect($audit['failure_reason'])->toBeNull();

    // Audit for state with no licenses
    $auditUnlicensed = $engine->auditLeadAssignment([
        'lead_pipeline_id' => $pipeline->id,
        'state_code' => 'GA',
        'preferred_language' => 'en',
    ]);

    expect($auditUnlicensed['licensed_pool_count'])->toEqual(0);
    expect($auditUnlicensed['is_routable'])->toBeFalse();
    expect($auditUnlicensed['failure_reason'])->toEqual('no_licensed_agents_in_state');
});
