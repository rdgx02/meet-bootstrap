@extends('layouts.app')

@section('title', 'Perfil')

@section('content')
    <div class="app-page app-form-shell">
        <section class="app-page-header-panel">
            <div class="app-page-header-copy">
                <div class="app-page-eyebrow">Conta</div>
                <h1 class="app-page-title">Perfil</h1>
                <p class="app-page-note">Atualize seus dados de acesso e configuracoes de seguranca.</p>
            </div>
        </section>

        <div class="app-profile-stack">
            <div class="app-card app-form-panel p-4 p-md-5">
                @include('profile.partials.update-profile-information-form')
            </div>

            <div class="app-card app-form-panel p-4 p-md-5">
                @include('profile.partials.update-password-form')
            </div>

            <div class="app-card app-form-panel p-4 p-md-5">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
@endsection
