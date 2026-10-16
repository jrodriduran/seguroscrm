<?php

namespace Webkul\Insurance\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Webkul\User\Repositories\UserRepository;

class ConfigureAgentIdentity extends Command
{
    protected $signature = 'insurance:agent-identity {email} {npn} {agency}';

    protected $description = 'Configure the assigned agent identity for future consent documents (does not verify licensing)';

    public function handle(UserRepository $users): int
    {
        $data = Validator::make($this->arguments(), [
            'email' => ['required', 'email'], 'npn' => ['required', 'regex:/^[1-9][0-9]{0,9}$/'],
            'agency' => ['required', 'string', 'max:255', 'not_regex:/^\s*$/'],
        ])->validate();
        $user = $users->findOneByField('email', $data['email']);
        if (! $user || ! $user->status) {
            $this->error('An existing active user is required.');

            return self::FAILURE;
        }
        $existing = DB::table('insurance_agent_identities')->where('user_id', $user->id)->exists();
        DB::table('insurance_agent_identities')->updateOrInsert(['user_id' => $user->id], [
            'npn' => $data['npn'], 'agency_name' => trim($data['agency']), 'updated_at' => now(),
            ...($existing ? [] : ['created_at' => now()]),
        ]);
        $this->info('Identity configured. Existing documents and signatures were not changed.');

        return self::SUCCESS;
    }
}
