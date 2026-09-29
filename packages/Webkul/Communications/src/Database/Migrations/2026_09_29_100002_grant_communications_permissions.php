<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Roles that can view contacts may open their communications; roles that
     * can edit contacts may record contact preferences and consent.
     */
    protected array $grants = [
        'contacts.persons.view' => 'contacts.persons.communications',
        'contacts.persons.edit' => 'contacts.persons.consent',
    ];

    public function up(): void
    {
        foreach (DB::table('roles')->where('permission_type', 'custom')->get(['id', 'permissions']) as $role) {
            $permissions = json_decode($role->permissions ?? '[]', true) ?: [];

            foreach ($this->grants as $has => $grant) {
                if (in_array($has, $permissions, true) && ! in_array($grant, $permissions, true)) {
                    $permissions[] = $grant;
                }
            }

            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode(array_values($permissions))]);
        }
    }

    public function down(): void
    {
        foreach (DB::table('roles')->where('permission_type', 'custom')->get(['id', 'permissions']) as $role) {
            $permissions = array_values(array_diff(json_decode($role->permissions ?? '[]', true) ?: [], array_values($this->grants)));

            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($permissions)]);
        }
    }
};
