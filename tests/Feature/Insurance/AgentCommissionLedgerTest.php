<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Lead\Models\AgentCommissionBalance;
use Webkul\Lead\Services\AgentLedgerService;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function createAgentLedgerUser(array $attributes = []): User
{
    $role = Role::first() ?: Role::create([
        'name' => 'Agent Role',
        'permission_type' => 'all',
    ]);

    return User::create(array_merge([
        'name' => 'Agent '.uniqid(),
        'email' => 'agent.'.uniqid().'@example.com',
        'password' => bcrypt('password123'),
        'status' => 1,
        'role_id' => $role->id,
    ], $attributes));
}

it('creates initial agent commission balance in good standing', function () {
    $user = createAgentLedgerUser();
    $service = app(AgentLedgerService::class);

    $balance = $service->getOrCreateBalance($user->id);

    expect($balance)->not->toBeNull();
    expect($balance->user_id)->toBe($user->id);
    expect($balance->current_balance)->toBe(0.0);
    expect($balance->status)->toBe('in_good_standing');
    expect($balance->is_negative)->toBeFalse();
});

it('credits commission and amortizes debt from prior clawbacks', function () {
    $user = createAgentLedgerUser();
    $service = app(AgentLedgerService::class);

    // 1. Initial Credit: $100.00
    $t1 = $service->recordCommissionCredit(
        userId: $user->id,
        amount: 100.00,
        policyNumber: 'POL-FLB-100',
        carrierName: 'Florida Blue',
        periodMonth: '2026-01'
    );

    expect($t1->balance_before)->toBe(0.0);
    expect($t1->balance_after)->toBe(100.0);
    expect($t1->transaction_type)->toBe('commission_credit');

    $balance = AgentCommissionBalance::where('user_id', $user->id)->first();
    expect($balance->current_balance)->toBe(100.0);
    expect($balance->total_earned)->toBe(100.0);

    // 2. Clawback of $160.00 -> balance becomes -$60.00 (debt alert)
    $t2 = $service->recordClawback(
        userId: $user->id,
        amount: 160.00,
        policyNumber: 'POL-FLB-100',
        carrierName: 'Florida Blue',
        periodMonth: '2026-01',
        reason: 'Póliza cancelada en primeros 30 días.'
    );

    expect($t2->balance_before)->toBe(100.0);
    expect($t2->balance_after)->toBe(-60.0);
    expect($t2->transaction_type)->toBe('clawback_debit');

    $balance->refresh();
    expect($balance->current_balance)->toBe(-60.0);
    expect($balance->total_clawbacks)->toBe(160.0);
    expect($balance->status)->toBe('negative_balance_alert');
    expect($balance->is_negative)->toBeTrue();

    // 3. New Commission Credit: $100.00 -> Automatically amortizes debt to +$40.00
    $t3 = $service->recordCommissionCredit(
        userId: $user->id,
        amount: 100.00,
        policyNumber: 'POL-AMB-200',
        carrierName: 'Ambetter',
        periodMonth: '2026-02'
    );

    expect($t3->balance_before)->toBe(-60.0);
    expect($t3->balance_after)->toBe(40.0);

    $balance->refresh();
    expect($balance->current_balance)->toBe(40.0);
    expect($balance->total_earned)->toBe(200.0);
    expect($balance->status)->toBe('in_good_standing');
    expect($balance->is_negative)->toBeFalse();
});

it('disburses net payout and rejects disbursement exceeding available balance', function () {
    $user = createAgentLedgerUser();
    $service = app(AgentLedgerService::class);

    $service->recordCommissionCredit(
        userId: $user->id,
        amount: 250.00,
        policyNumber: 'POL-300'
    );

    // Successful disbursement of $150.00
    $payout = $service->disbursePayout(
        userId: $user->id,
        amount: 150.00,
        referenceCode: 'ACH-TEST-9988'
    );

    expect($payout->balance_before)->toBe(250.0);
    expect($payout->balance_after)->toBe(100.0);
    expect($payout->amount)->toBe(-150.0);

    $balance = AgentCommissionBalance::where('user_id', $user->id)->first();
    expect($balance->current_balance)->toBe(100.0);
    expect($balance->total_paid_out)->toBe(150.0);
    expect($balance->last_payout_at)->not->toBeNull();

    // Attempt to disburse $150.00 when only $100.00 remains -> throws exception
    expect(fn () => $service->disbursePayout(
        userId: $user->id,
        amount: 150.00,
        referenceCode: 'ACH-TEST-FAIL'
    ))->toThrow(InvalidArgumentException::class);
});

it('provides agency ledger overview and agent detail endpoints via HTTP', function () {
    $admin = User::first() ?: createAgentLedgerUser();
    $agent = createAgentLedgerUser(['name' => 'Agent Patricia']);

    $service = app(AgentLedgerService::class);
    $service->recordCommissionCredit($agent->id, 300.00, 'POL-PAT-01', 'Oscar');

    // 1. Overview API
    $response = test()->actingAs($admin)->getJson('/admin/insurance/ledger');
    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'kpis' => [
                    'total_agents',
                    'total_payable_balance',
                    'total_negative_debt',
                    'agents_with_debt',
                ],
                'balances',
            ],
        ]);

    // 2. Agent History API
    $agentRes = test()->actingAs($admin)->getJson("/admin/insurance/ledger/{$agent->id}");
    $agentRes->assertOk()
        ->assertJsonStructure([
            'success',
            'agent' => ['id', 'name'],
            'balance',
            'transactions',
        ]);

    // 3. Post Clawback via API
    $clawbackRes = test()->actingAs($admin)->postJson("/admin/insurance/ledger/{$agent->id}/clawback", [
        'amount' => 50.00,
        'policy_number' => 'POL-PAT-01',
        'carrier_name' => 'Oscar',
        'reason' => 'Cancelación de póliza',
    ]);

    $clawbackRes->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    expect($clawbackRes->json('balance.current_balance'))->toEqual(250.0);

    // 4. Disburse via API
    $disburseRes = test()->actingAs($admin)->postJson("/admin/insurance/ledger/{$agent->id}/disburse", [
        'amount' => 200.00,
        'reference_code' => 'ACH-2026-PAT',
        'notes' => 'Liquidación quincenal',
    ]);

    $disburseRes->assertOk();
    expect($disburseRes->json('balance.current_balance'))->toEqual(50.0);
});
