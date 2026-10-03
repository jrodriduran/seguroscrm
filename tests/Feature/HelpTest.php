<?php

it('shows the help page to an authenticated admin', function () {
    $admin = getDefaultAdmin();

    test()->actingAs($admin)
        ->get(route('admin.help.index'))
        ->assertOk()
        ->assertSee('Help & Resources')
        ->assertSee('Support')
        ->assertSee('Pipeline playbook')
        ->assertSee('Follow-up center')
        ->assertDontSee('krayincrm.com');
});
