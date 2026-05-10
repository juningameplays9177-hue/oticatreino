<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = 'admin@hospitaldosoculos.com';
        
        if (!User::where('email', $email)->exists()) {
            User::create([
                'name' => 'Administrador',
                'email' => $email,
                'password' => Hash::make('Admin@2025'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);
            
            $this->command->info('Usuário administrador criado com sucesso!');
            $this->command->info('Email: ' . $email);
            $this->command->info('Senha: Admin@2025');
        } else {
            // Atualizar usuário existente para admin se não tiver role
            $user = User::where('email', $email)->first();
            if (!$user->role) {
                $user->update(['role' => 'admin']);
                $this->command->info('Role de administrador atribuído ao usuário existente!');
            } else {
                $this->command->warn('Usuário administrador já existe!');
            }
        }

        // Criar usuário gerente de exemplo
        $gerenteEmail = 'gerente@hospitaldosoculos.com';
        if (!User::where('email', $gerenteEmail)->exists()) {
            User::create([
                'name' => 'Gerente',
                'email' => $gerenteEmail,
                'password' => Hash::make('Gerente@2025'),
                'role' => 'gerente',
                'email_verified_at' => now(),
            ]);
            
            $this->command->info('Usuário gerente criado com sucesso!');
            $this->command->info('Email: ' . $gerenteEmail);
            $this->command->info('Senha: Gerente@2025');
        }
    }
}

