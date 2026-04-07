<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $managedUser = $this->route('user');

        return $managedUser instanceof User
            && ($this->user()?->can('update', $managedUser) ?? false);
    }

    public function rules(): array
    {
        /** @var User $managedUser */
        $managedUser = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($managedUser->id)],
            'role' => ['required', Rule::in(UserRole::values())],
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var User|null $managedUser */
            $managedUser = $this->route('user');
            $currentUser = $this->user();

            if (! $managedUser instanceof User || ! $currentUser instanceof User) {
                return;
            }

            $newRole = UserRole::from((string) $this->input('role'));
            $willBeActive = $this->boolean('is_active');
            $isSelf = $managedUser->is($currentUser);

            if ($isSelf && ! $willBeActive) {
                $validator->errors()->add('is_active', 'Você não pode desativar a própria conta.');
            }

            if ($isSelf && $newRole !== UserRole::Admin) {
                $validator->errors()->add('role', 'Você não pode remover o próprio acesso administrativo.');
            }

            $isManagedUserAdmin = $managedUser->role === UserRole::Admin;
            $removingAdminAccess = $isManagedUserAdmin && ($newRole !== UserRole::Admin || ! $willBeActive);

            if (! $removingAdminAccess) {
                return;
            }

            $activeAdminsCount = User::query()
                ->where('role', UserRole::Admin->value)
                ->where('is_active', true)
                ->count();

            if ($activeAdminsCount <= 1) {
                if ($newRole !== UserRole::Admin) {
                    $validator->errors()->add('role', 'Não é permitido remover o último administrador ativo do sistema.');
                }

                if (! $willBeActive) {
                    $validator->errors()->add('is_active', 'Não é permitido desativar o último administrador ativo do sistema.');
                }
            }
        });
    }
}
