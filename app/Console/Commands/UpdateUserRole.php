<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UpdateUserRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:role {email} {role}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza o role de um usuário';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $role = $this->argument('role');

        if (!in_array($role, ['admin', 'gerente'])) {
            $this->error('Role inválido. Use: admin ou gerente');
            return 1;
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Usuário com email '{$email}' não encontrado!");
            return 1;
        }

        $user->role = $role;
        $user->save();

        $this->info("Role do usuário '{$user->name}' atualizado para '{$role}' com sucesso!");
        return 0;
    }
}

