<x-filament-panels::page>
    <div class="flex items-center justify-between print:hidden mb-2">
        <h2 class="text-2xl font-bold tracking-tight">Tableau de bord financier</h2>
        <x-filament::button icon="heroicon-o-printer" onclick="window.print()" color="gray">
            Imprimer le rapport
        </x-filament::button>
    </div>

    <!-- Période Filters (print:hidden) -->
    <x-filament::section class="print:hidden">
        <x-slot name="heading">Définir la période</x-slot>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-1">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-200" for="report-start">Du :</label>
                <input
                    id="report-start"
                    type="date"
                    wire:model.live="startDate"
                    class="fi-input block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-base text-gray-950 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                />
            </div>
            <div class="space-y-1">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-200" for="report-end">Au :</label>
                <input
                    id="report-end"
                    type="date"
                    wire:model.live="endDate"
                    class="fi-input block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-base text-gray-950 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                />
            </div>
        </div>
    </x-filament::section>

    <!-- Print Title (Only visible in Print) -->
    <div class="hidden print:block mb-6 border-b pb-4">
        <h1 class="text-3xl font-bold text-gray-900">Rapport Financier & Activité</h1>
        <p class="text-gray-500 mt-1">Période du {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
    </div>

    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Synthèse Globale</x-slot>

            <dl class="grid gap-6 sm:grid-cols-3">
                <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 print:ring-gray-300 print:bg-white">
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Nouvelles inscriptions</dt>
                    <dd class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        {{ $this->getNewMembersInPeriod() }} <span class="text-lg font-medium text-gray-500">membres</span>
                    </dd>
                </div>
                <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 print:ring-gray-300 print:bg-white">
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Chiffre d’affaires total</dt>
                    <dd class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white text-green-600">
                        {{ number_format($this->getRevenueInPeriod(), 0, ',', ' ') }} <span class="text-lg font-medium text-gray-500">FCFA</span>
                    </dd>
                </div>
                <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 print:ring-gray-300 print:bg-white">
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Taux de renouvellement</dt>
                    <dd class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        @php $rate = $this->getRenewalRatePercent(); @endphp
                        @if ($rate === null)
                            <span class="text-lg text-gray-500">N/A</span>
                        @else
                            {{ $rate }}<span class="text-lg font-medium text-gray-500">%</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </x-filament::section>

        <!-- Breakdown by payment method -->
        <x-filament::section>
            <x-slot name="heading">Répartition par Mode de Paiement</x-slot>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($this->getPaymentsByMethod() as $method => $total)
                    <div class="rounded-lg bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 print:ring-gray-300 print:bg-white">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ \App\Enums\PaymentMethod::tryFrom($method)?->label() ?? $method }}</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($total, 0, ',', ' ') }} FCFA</p>
                    </div>
                @endforeach
                @if(empty($this->getPaymentsByMethod()))
                    <p class="text-gray-500 text-sm col-span-full">Aucun encaissement sur cette période.</p>
                @endif
            </div>
        </x-filament::section>

        <!-- Details table -->
        <x-filament::section>
            <x-slot name="heading">Détail des Transactions ({{ $this->getPaymentDetails()->count() }})</x-slot>
            <div class="overflow-hidden ring-1 ring-gray-950/5 rounded-lg dark:ring-white/10 print:ring-gray-300">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 dark:bg-white/5 print:bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 font-medium text-gray-950 dark:text-white">Date / Heure</th>
                            <th class="px-4 py-3 font-medium text-gray-950 dark:text-white">Membre</th>
                            <th class="px-4 py-3 font-medium text-gray-950 dark:text-white">Motif</th>
                            <th class="px-4 py-3 font-medium text-gray-950 dark:text-white">Mode</th>
                            <th class="px-4 py-3 font-medium text-gray-950 dark:text-white text-right">Montant</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5 print:divide-gray-300">
                        @forelse($this->getPaymentDetails() as $payment)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ \Carbon\Carbon::parse($payment->paid_at)->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 text-gray-900 dark:text-white font-medium">{{ $payment->member?->name ?? 'Client de passage' }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                    @if($payment->subscription_id)
                                        Abonnement <span class="text-xs ml-1 inline-flex items-center rounded-md bg-blue-50 px-2 py-1 font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">{{ $payment->subscription->plan->name }}</span>
                                    @else
                                        Séance unique <span class="text-xs ml-1 inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">Walk-in</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $payment->payment_method->label() }}</td>
                                <td class="px-4 py-3 text-gray-900 dark:text-white font-bold text-right">{{ number_format($payment->amount, 0, ',', ' ') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">Aucun encaissement sur cette période.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>

    <style>
        @media print {
            .fi-sidebar,
            .fi-topbar,
            .fi-breadcrumbs,
            .fi-page-header {
                display: none !important;
            }
            .fi-main {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            body {
                background-color: white !important;
            }
        }
    </style>
</x-filament-panels::page>
