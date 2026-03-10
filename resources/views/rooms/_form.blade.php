@php
    $isEdit = isset($room);
    $action = $isEdit ? route('rooms.update', $room) : route('rooms.store');
@endphp

<div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ $isEdit ? 'Editar Sala' : 'Nova Sala' }}</h2>
    <p class="text-sm text-gray-600 mb-6">
        {{ $isEdit ? 'Atualize os dados da sala.' : 'Cadastre uma nova sala para uso na agenda.' }}
    </p>

    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-semibold mb-1">Nao foi possivel salvar:</p>
            <ul class="list-disc ms-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-5">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div>
            <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nome da sala</label>
            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name', $isEdit ? $room->name : '') }}"
                maxlength="255"
                required
                class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="Ex.: Sala 203"
            >
        </div>

        <div>
            <input type="hidden" name="is_active" value="0">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                    @checked((bool) old('is_active', $isEdit ? $room->is_active : true))
                >
                Sala ativa para novos agendamentos
            </label>
        </div>

        <div class="mt-2 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4">
            <p class="text-xs text-gray-500">Use sala inativa para impedir novos agendamentos sem apagar historico.</p>

            <div class="flex items-center gap-3">
                <a
                    href="{{ route('rooms.index') }}"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                >
                    Cancelar
                </a>
                <button
                    type="submit"
                    class="inline-flex items-center rounded-md border border-transparent px-4 py-2 text-sm font-semibold shadow-sm"
                    style="background-color:#2563eb;color:#ffffff;"
                >
                    {{ $isEdit ? 'Salvar alteracoes' : 'Criar sala' }}
                </button>
            </div>
        </div>
    </form>
</div>
