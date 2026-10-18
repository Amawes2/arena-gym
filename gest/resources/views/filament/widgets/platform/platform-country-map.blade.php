<x-filament-widgets::widget>
    <x-filament::section heading="Répartition par pays" description="Salles et comptes gestionnaires enregistrés par pays.">
        @if (count($countries) === 0)
            <div class="rounded-xl border border-dashed border-gray-300 px-6 py-10 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                Aucune salle n’a encore renseigné de pays.
            </div>
        @else
            <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(16rem,1fr)]">
                <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-950">
                    <div class="relative aspect-[2/1]">
                        <img
                            src="{{ asset('images/world-land.svg') }}"
                            alt=""
                            aria-hidden="true"
                            class="absolute inset-0 h-full w-full object-contain opacity-90"
                        >

                        @foreach ($mapCountries as $country)
                            @php
                                $x = (($country['coordinates']['longitude'] + 180) / 360) * 100;
                                $y = ((90 - $country['coordinates']['latitude']) / 180) * 100;
                            @endphp

                            <span
                                class="absolute z-10 size-3 -translate-x-1/2 -translate-y-1/2 rounded-full border border-orange-200 bg-orange-500 shadow-lg shadow-orange-950/30"
                                style="left: {{ $x }}%; top: {{ $y }}%;"
                                title="{{ $country['name'] }} : {{ $country['gym_count'] }} salle(s)"
                                aria-label="{{ $country['name'] }} : {{ $country['gym_count'] }} salle(s)"
                            ></span>
                        @endforeach
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-gray-200 px-4 py-3 text-xs text-gray-500 dark:border-gray-800 dark:text-gray-400">
                        <span>{{ number_format($totalGyms, 0, ',', ' ') }} salle(s) renseignée(s) par pays</span>
                        @if ($unmappedGyms > 0)
                            <span>{{ $unmappedGyms }} sans position cartographique</span>
                        @endif
                    </div>
                </div>

                <div>
                    <h3 class="mb-3 text-sm font-semibold text-gray-950 dark:text-white">Principaux pays</h3>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach (array_slice($countries, 0, 8) as $country)
                            <div class="flex items-center justify-between gap-3 py-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-gray-950 dark:text-white">{{ $country['name'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $country['user_count'] }} utilisateur(s) associé(s)</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-orange-50 px-2.5 py-1 text-xs font-semibold text-orange-700 dark:bg-orange-500/10 dark:text-orange-300">
                                    {{ $country['gym_count'] }} salle(s)
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                Les marqueurs regroupent les salles par pays ; aucune adresse IP ni donnée nominative n’est affichée.
            </p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
