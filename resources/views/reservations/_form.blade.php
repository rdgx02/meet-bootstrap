@php
    $isEdit = isset($reservation);
    $formAction = $isEdit ? route('reservations.update', $reservation) : route('reservations.store');
    $dateValue = old('date', $isEdit ? $reservation->date : now()->toDateString());
    $startValue = old('start_time', $isEdit ? $reservation->start_time : '08:00');
    $endValue = old('end_time', $isEdit ? $reservation->end_time : '09:00');
    $conflictContext = session('reservation_conflict');
    $conflictDetails = is_array($conflictContext) ? $conflictContext : [];
    $conflictMessage = $errors->first('start_time');
    $showConflictAlert = $conflictDetails !== [] && $conflictMessage !== '';
    $conflictRoomName = $conflictDetails['room_name'] ?? '-';
    $conflictDate = $conflictDetails['date'] ?? '-';
    $conflictStart = $conflictDetails['start_time'] ?? '--:--';
    $conflictEnd = $conflictDetails['end_time'] ?? '--:--';
    $conflictTitle = $conflictDetails['title'] ?? '-';
    $conflictRequester = $conflictDetails['requester'] ?? '-';
    $otherErrors = collect($errors->all());

    if ($showConflictAlert) {
        $otherErrors = $otherErrors->reject(fn (string $error): bool => $error === $conflictMessage)->values();
    }
@endphp

<div class="max-w-3xl mx-auto">
    <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ $isEdit ? 'Editar Agendamento' : 'Novo Agendamento' }}</h2>
    <p class="text-sm text-gray-600 mb-6">
        {{ $isEdit ? 'Atualize os dados e salve as alteracoes.' : 'Preencha os dados para registrar um novo horario.' }}
    </p>

    @if ($showConflictAlert)
        <div class="mb-5 overflow-hidden rounded-xl border border-amber-300 bg-gradient-to-r from-amber-50 to-orange-50 text-amber-950 shadow-sm">
            <div class="flex items-start gap-3 border-b border-amber-200 bg-amber-100/60 px-4 py-3">
                <div class="flex h-7 w-7 items-center justify-center rounded-full bg-amber-200/80 text-amber-900">
                    !
                </div>
                <div>
                    <p class="text-sm font-semibold">Horario indisponivel para essa sala</p>
                    <p class="mt-0.5 text-xs text-amber-900/80">{{ $conflictMessage }}</p>
                </div>
            </div>

            <div class="space-y-3 px-4 py-4">
                <div class="rounded-lg border border-amber-200 bg-white/90 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-800">Horario ocupado</p>
                    <p class="mt-1 text-lg font-semibold leading-tight text-amber-950">{{ $conflictStart }} - {{ $conflictEnd }}</p>
                    <p class="mt-1 text-sm text-amber-900">{{ $conflictDate }} | Sala {{ $conflictRoomName }}</p>
                </div>

                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 text-sm">
                    <div class="rounded-lg border border-amber-200 bg-white/70 px-3 py-2">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-800">Titulo da reserva existente</p>
                        <p class="mt-1 font-medium text-amber-950">{{ $conflictTitle }}</p>
                    </div>
                    <div class="rounded-lg border border-amber-200 bg-white/70 px-3 py-2">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-800">Solicitante</p>
                        <p class="mt-1 font-medium text-amber-950">{{ $conflictRequester }}</p>
                    </div>
                </div>

                <p class="text-xs text-amber-900/90">
                    Sugestao: escolha outro horario livre ou altere a sala para concluir o agendamento.
                </p>
            </div>
        </div>
    @endif

    @if ($otherErrors->isNotEmpty())
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-semibold mb-1">Nao foi possivel salvar:</p>
            <ul class="list-disc ms-5 space-y-1">
                @foreach ($otherErrors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $formAction }}" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-5">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label for="room_id" class="block text-sm font-medium text-gray-700 mb-1">Sala</label>
                <select
                    id="room_id"
                    name="room_id"
                    required
                    class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">Selecione</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->id }}" @selected((string) old('room_id', $isEdit ? $reservation->room_id : '') === (string) $room->id)>
                            {{ $room->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Data</label>
                <input
                    id="date"
                    type="text"
                    name="date"
                    value="{{ $dateValue }}"
                    data-min-date="{{ now()->toDateString() }}"
                    required
                    class="js-date-picker w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="dd/mm/aaaa"
                >
            </div>

            <div>
                <label for="requester" class="block text-sm font-medium text-gray-700 mb-1">Solicitante</label>
                <input
                    id="requester"
                    type="text"
                    name="requester"
                    value="{{ old('requester', $isEdit ? $reservation->requester : '') }}"
                    maxlength="255"
                    required
                    class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="Nome de quem pediu a reserva"
                >
            </div>

            <div>
                <label for="start_time" class="block text-sm font-medium text-gray-700 mb-1">Hora inicio</label>
                <input
                    id="start_time"
                    type="time"
                    name="start_time"
                    value="{{ $startValue }}"
                    required
                    class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                >
            </div>

            <div>
                <label for="end_time" class="block text-sm font-medium text-gray-700 mb-1">Hora fim</label>
                <input
                    id="end_time"
                    type="time"
                    name="end_time"
                    value="{{ $endValue }}"
                    required
                    class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                >
            </div>

            <div class="sm:col-span-2">
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Titulo</label>
                <input
                    id="title"
                    type="text"
                    name="title"
                    value="{{ old('title', $isEdit ? $reservation->title : '') }}"
                    maxlength="255"
                    required
                    class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="Ex.: Reuniao de equipe"
                >
            </div>

            <div class="sm:col-span-2">
                <label for="contact" class="block text-sm font-medium text-gray-700 mb-1">Contato (opcional)</label>
                <input
                    id="contact"
                    type="text"
                    name="contact"
                    value="{{ old('contact', $isEdit ? $reservation->contact : '') }}"
                    maxlength="255"
                    class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="Telefone, ramal ou e-mail"
                >
            </div>
        </div>

        <div class="pt-4 mt-2 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs text-gray-500">Revise os dados e clique em salvar para concluir.</p>

            <div class="flex items-center gap-3">
                <a href="{{ route('reservations.index') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Cancelar
                </a>
                <button
                    type="submit"
                    class="inline-flex items-center rounded-md border border-transparent px-4 py-2 text-sm font-semibold shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2"
                    style="background-color:#2563eb;color:#ffffff;"
                >
                    {{ $isEdit ? 'Salvar alteracoes' : 'Criar agendamento' }}
                </button>
            </div>
        </div>
    </form>
</div>
