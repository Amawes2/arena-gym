<x-filament-panels::page>
    @php
        $tone = $this->getStatusTone();

        $icon = match ($tone) {
            'danger' => 'heroicon-o-lock-closed',
            'warning' => 'heroicon-o-clock',
            'success' => 'heroicon-o-shield-check',
            default => 'heroicon-o-question-mark-circle',
        };
    @endphp

    <div class="gym-billing gym-billing--{{ $tone }}">
        <section class="gym-billing__hero">
            <div class="gym-billing__hero-main">
                <div class="gym-billing__eyebrow">
                    <span class="gym-billing__pill">gestgym pro</span>
                    <span class="gym-billing__status">{{ $this->getStatusLabel() }}</span>

                    @if ($this->hasGym())
                        <span class="gym-billing__plan">{{ $this->getPlanLabel() }}</span>
                    @endif
                </div>

                <div class="gym-billing__hero-copy">
                    <div class="gym-billing__hero-text">
                        <h2 class="gym-billing__title">{{ $this->getHeroTitle() }}</h2>
                        <p class="gym-billing__description">{{ $this->getHeroDescription() }}</p>
                    </div>

                    <div class="gym-billing__hero-icon">
                        <x-filament::icon :icon="$icon" class="h-8 w-8" />
                    </div>
                </div>

                <dl class="gym-billing__metrics">
                    <div class="gym-billing__metric">
                        <dt class="gym-billing__metric-label">Échéance</dt>
                        <dd class="gym-billing__metric-value">{{ $this->getExpiryDisplay() }}</dd>
                        <p class="gym-billing__metric-note">{{ $this->getRelativeExpiryLabel() }}</p>
                    </div>

                    <div class="gym-billing__metric">
                        <dt class="gym-billing__metric-label">Accès</dt>
                        <dd class="gym-billing__metric-value">{{ $this->getAccessLabel() }}</dd>
                        <p class="gym-billing__metric-note">Lecture instantanée du niveau d’exploitation.</p>
                    </div>

                    <div class="gym-billing__metric">
                        <dt class="gym-billing__metric-label">Salle liée</dt>
                        <dd class="gym-billing__metric-value">{{ $this->gym?->name ?? 'Non trouvée' }}</dd>
                        <p class="gym-billing__metric-note">
                            {{ $this->gym?->slug ? '#'.$this->gym->slug : 'Aucun slug disponible.' }}
                        </p>
                    </div>
                </dl>
            </div>

            <aside class="gym-billing__hero-aside">
                <div class="gym-billing__health-card">
                    <div class="gym-billing__health-head">
                        <div>
                            <p class="gym-billing__aside-label">Santé abonnement</p>
                            <p class="gym-billing__health-score">{{ $this->getHealthPercent() }}%</p>
                        </div>

                        <div class="gym-billing__health-badge">
                            <x-filament::icon :icon="$icon" class="h-4 w-4" />
                            <span>{{ $this->getPriorityLabel() }}</span>
                        </div>
                    </div>

                    <div class="gym-billing__health-bar">
                        <span class="gym-billing__health-fill" style="width: {{ $this->getHealthPercent() }}%; --fill-width: {{ $this->getHealthPercent() }}%;"></span>
                    </div>

                    <p class="gym-billing__health-text">{{ $this->getPriorityDescription() }}</p>
                </div>

                <div class="gym-billing__aside-card">
                    <div class="gym-billing__identity">
                        <div class="gym-billing__identity-icon">
                            <x-filament::icon icon="heroicon-o-building-office-2" class="h-5 w-5" />
                        </div>

                        <div>
                            <p class="gym-billing__identity-label">Salle concernée</p>
                            <p class="gym-billing__identity-value">{{ $this->gym?->name ?? 'Compte sans salle liée' }}</p>
                        </div>
                    </div>

                    <dl class="gym-billing__facts">
                        <div class="gym-billing__fact">
                            <dt class="gym-billing__fact-label">Statut salle</dt>
                            <dd class="gym-billing__fact-value">
                                @if ($this->hasGym())
                                    {{ $this->gym->is_active ? 'Salle active' : 'Salle désactivée' }}
                                @else
                                    Inconnu
                                @endif
                            </dd>
                        </div>

                        <div class="gym-billing__fact">
                            <dt class="gym-billing__fact-label">Tarif séance unique</dt>
                            <dd class="gym-billing__fact-value">{{ $this->getFormattedWalkInPrice() }}</dd>
                        </div>

                        <div class="gym-billing__fact">
                            <dt class="gym-billing__fact-label">Dernière mise à jour</dt>
                            <dd class="gym-billing__fact-value">{{ $this->gym?->updated_at?->format('d/m/Y') ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="gym-billing__renewal">
                    <p class="gym-billing__renewal-label">Renouvellement</p>
                    <p class="gym-billing__renewal-text">{{ $this->getSupportHint() }}</p>

                    <div class="gym-billing__renewal-actions">
                        <x-filament::button color="warning" size="lg" disabled class="w-full justify-center">
                            Paiement en ligne bientôt disponible
                        </x-filament::button>
                    </div>
                </div>
            </aside>
        </section>

        <div class="gym-billing__grid">
            <x-filament::section>
                <x-slot name="heading">Fiche de la salle</x-slot>
                <x-slot name="description">
                    Les informations visibles pour contrôler la formule active et le contexte de facturation.
                </x-slot>

                <div class="gym-billing__details">
                    <div class="gym-billing__detail-card">
                        <p class="gym-billing__detail-label">Nom de la salle</p>
                        <p class="gym-billing__detail-value">{{ $this->gym?->name ?? 'Non disponible' }}</p>
                    </div>

                    <div class="gym-billing__detail-card">
                        <p class="gym-billing__detail-label">Slug</p>
                        <p class="gym-billing__detail-value">{{ $this->gym?->slug ?? 'Non disponible' }}</p>
                    </div>

                    <div class="gym-billing__detail-card">
                        <p class="gym-billing__detail-label">Formule</p>
                        <p class="gym-billing__detail-value">{{ $this->getPlanLabel() }}</p>
                    </div>

                    <div class="gym-billing__detail-card">
                        <p class="gym-billing__detail-label">Niveau d’accès</p>
                        <p class="gym-billing__detail-value">{{ $this->getAccessLabel() }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Impact opérationnel</x-slot>
                <x-slot name="description">
                    Ce que l’état actuel implique pour la salle, l’accueil et la continuité du service.
                </x-slot>

                <div class="gym-billing__list">
                    @foreach ($this->getImpactItems() as $item)
                        <div class="gym-billing__list-item">
                            <div class="gym-billing__list-icon">
                                <x-filament::icon :icon="$icon" class="h-4 w-4" />
                            </div>

                            <p class="gym-billing__list-text">{{ $item }}</p>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">Plan d’action</x-slot>
            <x-slot name="description">
                Les trois actions utiles pour clarifier puis stabiliser la situation de facturation.
            </x-slot>

            <div class="gym-billing__steps">
                @foreach ($this->getNextSteps() as $index => $step)
                    <div class="gym-billing__step">
                        <div class="gym-billing__step-head">
                            <div class="gym-billing__step-index">{{ $index + 1 }}</div>
                            <p class="gym-billing__step-label">Étape {{ $index + 1 }}</p>
                        </div>

                        <p class="gym-billing__step-text">{{ $step }}</p>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
