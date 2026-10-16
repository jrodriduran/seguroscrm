<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Agency;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Stage;
use Webkul\Teamwork\Models\FollowUp;
use Webkul\Teamwork\Models\Notification;
use Webkul\Teamwork\Services\TeamScope;
use Webkul\Teamwork\Services\WorkQueue;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function pilotTeamScenario(): array
{
    $owner = getDefaultAdmin();
    $agency = Agency::create(['name' => 'Synthetic Pilot Agency', 'code' => 'synthetic-pilot', 'status' => 'active']);
    $owner->update(['agency_id' => $agency->id]);
    $role = Role::create(['name' => 'Synthetic Pilot Agent', 'permission_type' => 'custom', 'permissions' => ['follow_up']]);
    $agents = collect([1, 2])->map(fn ($i) => User::create([
        'name' => 'Synthetic Agent '.$i, 'email' => 'daily-agent'.$i.'@example.invalid',
        'password' => bcrypt('synthetic'), 'status' => 1, 'role_id' => $role->id,
        'agency_id' => $agency->id, 'view_permission' => 'individual',
    ]));
    $person = Person::create(['name' => 'Synthetic Daily Client']);
    $pipeline = Pipeline::firstOrFail();
    $stage = Stage::where('lead_pipeline_id', $pipeline->id)->firstOrFail();
    $lead = Lead::create([
        'title' => 'Synthetic Daily Case', 'user_id' => $agents[0]->id, 'agency_id' => $agency->id,
        'person_id' => $person->id, 'lead_pipeline_id' => $pipeline->id, 'lead_pipeline_stage_id' => $stage->id,
    ]);

    return compact('owner', 'agents', 'lead', 'agency');
}

it('supports daily assignment overdue review completion and owner supervision for two agents', function () {
    extract(pilotTeamScenario());
    $this->actingAs($owner)->post(route('admin.teamwork.follow_ups.store'), [
        'entity_type' => 'lead', 'entity_id' => $lead->id, 'assigned_to' => $agents[0]->id,
        'due_at' => now()->subDay()->toDateTimeString(), 'priority' => 'urgent', 'note' => 'Synthetic daily review',
    ])->assertRedirect();
    $task = FollowUp::where('entity_type', 'lead')->where('entity_id', $lead->id)->sole();
    expect($task->created_by)->toBe($owner->id)->and($task->assigned_to)->toBe($agents[0]->id);
    expect(Notification::where('user_id', $agents[0]->id)->where('type', 'follow_up_urgent')->exists())->toBeTrue();
    expect(app(WorkQueue::class)->openFollowUps([$agents[0]->id])->firstWhere('id', $task->id)->state)->toBe('overdue');

    $this->actingAs($agents[1])->post(route('admin.teamwork.follow_ups.resolve', $task->id))->assertForbidden();
    $this->actingAs($agents[0])->post(route('admin.teamwork.follow_ups.resolve', $task->id), ['body' => 'Synthetic completion'])->assertRedirect();
    expect($task->fresh()->status)->toBe('done')->and($task->fresh()->resolved_by)->toBe($agents[0]->id);
    expect(app(WorkQueue::class)->teamSummary($agents->pluck('id')->all())->firstWhere('id', $agents[0]->id)->follow_ups)->toBe(0);
    $this->actingAs($owner)->get(route('admin.teamwork.center', ['tab' => 'team']))->assertOk();

    $this->actingAs($agents[1])->post(route('admin.teamwork.follow_ups.reopen', $task->id))->assertForbidden();
    expect($task->fresh()->status)->toBe('done');
    $this->actingAs($owner)->post(route('admin.teamwork.follow_ups.reopen', $task->id))->assertRedirect();
    expect($task->fresh()->status)->toBe('open')->and($task->fresh()->resolved_by)->toBeNull();
});

it('does not turn an unassigned agency into global team access', function () {
    extract(pilotTeamScenario());
    $unassigned = User::create(['name' => 'Synthetic Unassigned', 'email' => 'daily-unassigned@example.invalid', 'password' => bcrypt('synthetic'), 'status' => 1, 'role_id' => $owner->role_id, 'view_permission' => 'global']);
    $scope = app(TeamScope::class);
    expect($scope->teamUserIds($unassigned))->not->toContain($owner->id, $agents[0]->id, $agents[1]->id);
    expect($scope->supervisedUserIds($unassigned))->not->toContain($owner->id, $agents[0]->id);
    expect($scope->assignableUsers($unassigned)->pluck('id')->all())->not->toContain($owner->id);
});

it('rejects follow-up assignment to an agent outside the team', function () {
    extract(pilotTeamScenario());
    $other = User::create(['name' => 'Synthetic Outside Agent', 'email' => 'daily-outside@example.invalid', 'password' => bcrypt('synthetic'), 'status' => 1, 'role_id' => $owner->role_id, 'view_permission' => 'individual']);
    $this->actingAs($owner)->post(route('admin.teamwork.follow_ups.store'), [
        'entity_type' => 'lead', 'entity_id' => $lead->id, 'assigned_to' => $other->id,
    ])->assertForbidden();
    expect(FollowUp::where('entity_type', 'lead')->where('entity_id', $lead->id)->count())->toBe(0);
});
