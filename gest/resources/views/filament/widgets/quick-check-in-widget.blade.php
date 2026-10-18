<x-filament-widgets::widget>
    <x-filament::card>
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-bold tracking-tight">Entrée à la salle</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Rechercher un client puis pointer son passage.</p>
            </div>
            {{ $this->walkInAction }}
        </div>
        
        <x-filament-actions::modals />

        <div class="space-y-6">

        {{-- ── Recherche membre ───────────────────────────────────────────── --}}
        <div class="max-w-xl">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                Nom ou téléphone
            </label>
            <input
                type="text"
                wire:model.live.debounce.300ms="searchQuery"
                placeholder="Ex. Diallo ou 77…"
                class="mb-2 fi-input block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
            />
            <select
                wire:model.live="memberId"
                class="fi-select-input block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
            >
                <option value="">— Sélectionner un membre —</option>
                @foreach($this->memberOptions as $id => $label)
                    <option value="{{ $id }}">
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500">Jusqu’à 30 résultats.</p>
        </div>

        {{-- ── Carte statut membre ─────────────────────────────────────────── --}}
        @if($this->selectedMember)
            @php
                $member = $this->selectedMember;
                $sub    = $this->activeSubscription;
                $isOk   = $sub?->isAccessible();
            @endphp

            <div class="border-t pt-4 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-4">
                    @if($member->photo_path)
                        <img src="{{ Storage::url($member->photo_path) }}"
                             class="h-10 w-10 rounded-full object-cover" alt="">
                    @else
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-100 text-primary-700 font-bold text-sm dark:bg-primary-900 dark:text-primary-300">
                            {{ strtoupper(substr($member->first_name, 0, 1) . substr($member->last_name, 0, 1)) }}
                        </div>
                    @endif
                    <span class="font-bold text-lg">{{ $member->name }}</span>
                </div>

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-6">
                    {{-- Téléphone --}}
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Téléphone</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $member->phone }}</p>
                    </div>

                    {{-- Type --}}
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Type</p>
                        <span @class([
                            'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $member->isSubscriber(),
                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $member->isWalkIn(),
                        ])>{{ $member->client_type->label() }}</span>
                    </div>

                    {{-- Forfait --}}
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Forfait</p>
                        <p class="font-semibold text-gray-900 dark:text-white">
                            {{ $sub?->plan?->name ?? '—' }}
                        </p>
                    </div>

                    {{-- Expire le --}}
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Expire le</p>
                        <p @class([
                            'font-semibold',
                            'text-red-600 dark:text-red-400'   => $sub?->end_date?->isPast(),
                            'text-green-600 dark:text-green-400' => $sub && !$sub->end_date->isPast(),
                            'text-gray-500' => !$sub,
                        ])>
                            {{ $sub?->end_date?->format('d/m/Y') ?? '—' }}
                        </p>
                    </div>
                </div>

                {{-- Séances restantes --}}
                @if($sub?->sessions_remaining !== null)
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Séances restantes</span>
                            <span @class([
                                'text-sm font-bold',
                                'text-red-600'   => $sub->sessions_remaining <= 2,
                                'text-yellow-600' => $sub->sessions_remaining <= 5 && $sub->sessions_remaining > 2,
                                'text-green-600' => $sub->sessions_remaining > 5,
                            ])>{{ $sub->sessions_remaining }}</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                            @php $pct = min(100, ($sub->sessions_remaining / max(1, $sub->plan?->sessions_limit ?? 1)) * 100) @endphp
                            <div class="h-2 rounded-full transition-all"
                                 style="width: {{ $pct }}%; background-color: {{ $sub->sessions_remaining <= 2 ? '#dc2626' : ($sub->sessions_remaining <= 5 ? '#d97706' : '#16a34a') }}">
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Statut global --}}
                <div class="flex items-center gap-4">
                    @if($isOk)
                        <div class="flex items-center gap-2 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 px-4 py-3 flex-1">
                            <x-filament::icon icon="heroicon-o-check-circle" class="h-6 w-6 text-green-600 dark:text-green-400" />
                            <div>
                                <p class="font-semibold text-green-800 dark:text-green-200">Accès autorisé</p>
                                <p class="text-xs text-green-600 dark:text-green-400">Abonnement valide — pointer l'entrée</p>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-2 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 px-4 py-3 flex-1">
                            <x-filament::icon icon="heroicon-o-x-circle" class="h-6 w-6 text-red-600 dark:text-red-400" />
                            <div>
                                <p class="font-semibold text-red-800 dark:text-red-200">Accès refusé</p>
                                <p class="text-xs text-red-600 dark:text-red-400">
                                    @if(!$sub) Aucun abonnement actif
                                    @elseif($sub->end_date->isPast()) Abonnement expiré le {{ $sub->end_date->format('d/m/Y') }}
                                    @elseif($sub->sessions_remaining !== null && $sub->sessions_remaining <= 0) Plus de séances disponibles
                                    @else Abonnement non accessible
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endif

                    {{-- Bouton principal --}}
                    <x-filament::button
                        wire:click="recordCheckIn"
                        :disabled="!$isOk"
                        size="xl"
                        color="{{ $isOk ? 'success' : 'gray' }}"
                        icon="heroicon-o-arrow-right-end-on-rectangle"
                    >
                        Pointer l'entrée
                    </x-filament::button>
                </div>
            </div>
        @endif

        </div>
    </x-filament::card>
</x-filament-widgets::widget>
