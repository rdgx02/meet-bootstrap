<section x-data="{ showDeleteModal: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }">
    <header>
        <h2 class="h4 mb-1">Excluir conta</h2>
        <p class="text-body-secondary mb-0">Essa acao remove permanentemente seu acesso e os dados associados a conta.</p>
    </header>

    <div class="mt-4">
        <button type="button" class="btn btn-outline-danger" x-on:click="showDeleteModal = true">
            Excluir conta
        </button>
    </div>

    <template x-if="showDeleteModal">
        <div>
            <div class="app-modal-backdrop" x-on:click="showDeleteModal = false"></div>

            <div class="app-modal-shell" role="dialog" aria-modal="true" aria-labelledby="deleteAccountTitle">
                <div class="app-modal-card">
                    <form method="post" action="{{ route('profile.destroy') }}">
                        @csrf
                        @method('delete')

                        <div class="app-modal-header">
                            <div>
                                <span class="app-modal-kicker">Acao permanente</span>
                                <h2 id="deleteAccountTitle" class="app-modal-title">Excluir conta?</h2>
                            </div>

                            <button type="button" class="btn-close" aria-label="Fechar" x-on:click="showDeleteModal = false"></button>
                        </div>

                        <div class="app-modal-body">
                            <div class="app-delete-alert">
                                Essa operacao encerra permanentemente seu acesso ao sistema.
                            </div>

                            <p class="app-modal-text">
                                Essa acao remove permanentemente sua conta. Digite sua senha para confirmar.
                            </p>

                            <div>
                                <label for="password" class="form-label app-form-label">Senha</label>
                                <input id="password" name="password" type="password" class="form-control" placeholder="Senha atual" />
                                @if ($errors->userDeletion->has('password'))
                                    <div class="invalid-feedback d-block">{{ $errors->userDeletion->first('password') }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="app-modal-footer">
                            <button type="button" class="btn btn-outline-secondary app-section-btn app-section-btn-light" x-on:click="showDeleteModal = false">
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-danger app-delete-confirm-btn">
                                Excluir conta
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>
</section>
