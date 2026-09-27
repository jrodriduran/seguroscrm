<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\CarrierStatement;
use Webkul\Lead\Models\CarrierStatementItem;
use Webkul\Lead\Models\InsuranceCommission;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function createCommissionTestEnvironment(): array
{
    $pipeline = Pipeline::first() ?: Pipeline::create(['name' => 'Commissions Pipeline', 'is_default' => 1]);
    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->first() ?: Stage::create(['name' => 'Won', 'code' => 'won', 'lead_pipeline_id' => $pipeline->id]);

    $user = User::first() ?: User::factory()->create();

    $person = Person::create([
        'name' => 'Roberto Diaz',
        'emails' => [['value' => 'roberto.diaz@example.com', 'label' => 'work']],
        'contact_numbers' => [['value' => '3055551234', 'label' => 'mobile']],
    ]);

    $lead = Lead::create([
        'title' => 'Roberto Diaz - Florida Blue',
        'lead_pipeline_id' => $pipeline->id,
        'lead_pipeline_stage_id' => $stage->id,
        'person_id' => $person->id,
        'user_id' => $user->id,
    ]);

    $commission = InsuranceCommission::create([
        'carrier_name' => 'Florida Blue',
        'policy_number' => 'POL-FLB-IDEMP-01',
        'rate_pmpm' => 45.00,
        'members_count' => 1,
        'gross_monthly' => 45.00,
        'agent_split_percent' => 70.00,
        'agent_monthly' => 31.50,
        'agency_monthly' => 13.50,
        'status' => 'pending',
        'commission_type' => 'pmpm',
        'lead_id' => $lead->id,
        'user_id' => $user->id,
    ]);

    return compact('user', 'lead', 'commission');
}

it('prevents duplicate statement file uploads via cryptographic SHA-256 hash', function () {
    $env = createCommissionTestEnvironment();
    $user = $env['user'];

    $csvContent = "policy_number,insured_name,carrier_amount\nPOL-FLB-IDEMP-01,Roberto Diaz,45.00\n";

    // 1st Upload: Success
    $response1 = test()->actingAs($user)->postJson('/admin/insurance/commissions/upload-statement', [
        'carrier_name' => 'Florida Blue',
        'period_month' => '2026-03',
        'csv_content' => $csvContent,
    ]);

    $response1->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $statement = CarrierStatement::where('carrier_name', 'Florida Blue')
        ->where('period_month', '2026-03')
        ->first();

    expect($statement)->not->toBeNull();
    expect($statement->file_hash)->toBe(hash('sha256', trim($csvContent)));

    // 2nd Upload: Exact same content -> HTTP 409 Conflict
    $response2 = test()->actingAs($user)->postJson('/admin/insurance/commissions/upload-statement', [
        'carrier_name' => 'Florida Blue',
        'period_month' => '2026-03',
        'csv_content' => $csvContent,
    ]);

    $response2->assertStatus(409)
        ->assertJson([
            'success' => false,
            'is_duplicate' => true,
            'existing_statement_id' => $statement->id,
            'file_hash' => $statement->file_hash,
        ]);
});

it('blocks duplicate commission line payout for the same carrier, policy and period month', function () {
    $env = createCommissionTestEnvironment();
    $user = $env['user'];

    // Statement 1: First payout for 2026-04
    $csv1 = "policy_number,insured_name,carrier_amount\nPOL-FLB-IDEMP-01,Roberto Diaz,45.00\n";

    $response1 = test()->actingAs($user)->postJson('/admin/insurance/commissions/upload-statement', [
        'carrier_name' => 'Florida Blue',
        'period_month' => '2026-04',
        'csv_content' => $csv1,
    ]);
    $response1->assertOk();

    $stmt1 = CarrierStatement::where('period_month', '2026-04')->first();
    $item1 = CarrierStatementItem::where('carrier_statement_id', $stmt1->id)->first();
    expect($item1->match_status)->toBe('matched_exact');

    // Statement 2: Different CSV (e.g. supplemental statement) but contains duplicate payout for same policy and same month 2026-04
    $csv2 = "policy_number,insured_name,carrier_amount\nPOL-FLB-IDEMP-01,Roberto Diaz,45.00\n# Additional header comment to change file hash\n";

    $response2 = test()->actingAs($user)->postJson('/admin/insurance/commissions/upload-statement', [
        'carrier_name' => 'Florida Blue',
        'period_month' => '2026-04',
        'csv_content' => $csv2,
    ]);

    $response2->assertOk();

    $stmt2 = CarrierStatement::orderBy('id', 'desc')->first();
    expect($stmt2->id)->not->toBe($stmt1->id);
    expect($stmt2->duplicate_records)->toBe(1);
    expect($stmt2->total_duplicate_amount)->toBe(45.0);

    $dupItem = CarrierStatementItem::where('carrier_statement_id', $stmt2->id)->first();
    expect($dupItem->match_status)->toBe('duplicate_blocked');
    expect($dupItem->is_duplicate)->toBeTrue();
    expect($dupItem->duplicate_of_item_id)->toBe($item1->id);
    expect($dupItem->notes)->toContain('Pago duplicado bloqueado');
});

it('blocks intra-file duplicate records for the same policy in the same CSV upload', function () {
    $env = createCommissionTestEnvironment();
    $user = $env['user'];

    // CSV containing the same policy twice in the same batch
    $csvWithDuplicates = "policy_number,insured_name,carrier_amount\nPOL-FLB-IDEMP-01,Roberto Diaz,45.00\nPOL-FLB-IDEMP-01,Roberto Diaz,45.00\n";

    $response = test()->actingAs($user)->postJson('/admin/insurance/commissions/upload-statement', [
        'carrier_name' => 'Florida Blue',
        'period_month' => '2026-05',
        'csv_content' => $csvWithDuplicates,
    ]);

    $response->assertOk();

    $statement = CarrierStatement::where('period_month', '2026-05')->first();
    expect($statement->matched_records)->toBe(1);
    expect($statement->duplicate_records)->toBe(1);
    expect($statement->total_duplicate_amount)->toBe(45.0);

    $items = CarrierStatementItem::where('carrier_statement_id', $statement->id)->get();
    expect($items[0]->match_status)->toBe('matched_exact');
    expect($items[1]->match_status)->toBe('duplicate_blocked');
    expect($items[1]->is_duplicate)->toBeTrue();
});

it('allows normal payouts for different period months for the same policy', function () {
    $env = createCommissionTestEnvironment();
    $user = $env['user'];

    // Month 1: 2026-01
    $csvJan = "policy_number,insured_name,carrier_amount\nPOL-FLB-IDEMP-01,Roberto Diaz,45.00\n";
    $resJan = test()->actingAs($user)->postJson('/admin/insurance/commissions/upload-statement', [
        'carrier_name' => 'Florida Blue',
        'period_month' => '2026-01',
        'csv_content' => $csvJan,
    ]);
    $resJan->assertOk();

    // Month 2: 2026-02 (Legitimate recurring monthly commission)
    $csvFeb = "policy_number,insured_name,carrier_amount\nPOL-FLB-IDEMP-01,Roberto Diaz,45.00\n# Feb month\n";
    $resFeb = test()->actingAs($user)->postJson('/admin/insurance/commissions/upload-statement', [
        'carrier_name' => 'Florida Blue',
        'period_month' => '2026-02',
        'csv_content' => $csvFeb,
    ]);
    $resFeb->assertOk();

    $stmtFeb = CarrierStatement::where('period_month', '2026-02')->first();
    expect($stmtFeb->matched_records)->toBe(1);
    expect($stmtFeb->duplicate_records)->toBe(0);
});
