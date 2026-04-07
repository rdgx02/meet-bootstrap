<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

class UserRoleCommand extends Command
{
    /**
     * Nome do comando
     */
    protected $signature = 'user:role {user_id} {role}';

    /**
     * Descrição
     */
    protected $description = 'Define o papel (role) de um usuário: admin, secretary ou user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $role = UserRole::tryFrom(strtolower($this->argument('role')));

        if ($role === null) {
            $allowed = implode(', ', UserRole::values());
            $this->error("Role invalida. Use: {$allowed}");

            return Command::FAILURE;
        }

        // buscar usuário
        $user = User::find($userId);

        if (! $user) {
            $this->error("Usuário ID {$userId} não encontrado.");

            return Command::FAILURE;
        }

        // atualizar role
        $user->role = $role;
        $user->save();

        $this->info("Usuário {$user->name} agora é {$role->value}.");

        return Command::SUCCESS;
    }
}
