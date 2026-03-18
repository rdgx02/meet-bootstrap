@php
    use App\Enums\UserRole;
@endphp

@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
    <div class="app-module-shell">
        <section class="app-module-header">
            <div>
                <div class="app-module-kicker">Administracao</div>
                <h1 class="app-module-title">Usuarios</h1>
                <p class="app-module-note">Gerencie acesso, papeis e estado operacional das contas do sistema.</p>
            </div>

            @can('create', \App\Models\User::class)
                <a href="{{ route('users.create') }}" class="btn app-btn-primary app-section-btn">
                    Novo usuario
                </a>
            @endcan
        </section>

        @if (session('success'))
            <div class="alert alert-success app-success-alert" role="alert">
                {{ session('success') }}
            </div>
        @endif

        <section class="app-subpanel">
            <div class="app-subpanel-head">
                <div>
                    <h2 class="app-subpanel-title">Lista de usuarios</h2>
                    <p class="app-subpanel-note">Visao administrativa das contas autenticaveis no produto.</p>
                </div>
                <div class="app-subpanel-meta">
                    <span class="app-mini-stat">Total <strong>{{ $users->count() }}</strong></span>
                </div>
            </div>

            <div class="app-data-sheet">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 app-record-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>Papel</th>
                                <th>Status</th>
                                <th class="text-end">Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $managedUser)
                                <tr>
                                    <td class="fw-semibold">{{ $managedUser->name }}</td>
                                    <td>{{ $managedUser->email }}</td>
                                    <td>
                                        {{ match ($managedUser->role) {
                                            UserRole::Admin => 'Administrador',
                                            UserRole::Secretary => 'Secretaria',
                                            UserRole::User => 'Usuario',
                                        } }}
                                    </td>
                                    <td>
                                        <span class="app-status-pill {{ $managedUser->is_active ? 'is-active' : 'is-inactive' }}">
                                            {{ $managedUser->is_active ? 'Ativo' : 'Inativo' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="app-inline-actions justify-content-end">
                                            @can('update', $managedUser)
                                                <a href="{{ route('users.edit', $managedUser) }}" class="btn btn-sm app-ghost-btn">
                                                    Editar
                                                </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-body-secondary py-5">
                                        Nenhum usuario cadastrado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
@endsection
