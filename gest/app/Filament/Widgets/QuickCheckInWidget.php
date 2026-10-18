<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentMethod;
use App\Models\Member;
use App\Models\Subscription;
use App\Services\CheckInService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Livewire\Attributes\Computed;

class QuickCheckInWidget extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected string $view = 'filament.widgets.quick-check-in-widget';

    protected static ?int $sort = 1; // Tout en haut du dashboard

    protected int|string|array $columnSpan = 'full';

    public ?int $memberId = null;

    public string $searchQuery = '';

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function memberOptions(): array
    {
        return Member::query()
            ->when($this->searchQuery !== '', function ($query): void {
                $search = "%{$this->searchQuery}%";

                $query->where(function ($nested) use ($search): void {
                    $nested
                        ->where('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search)
                        ->orWhere('phone', 'like', $search)
                        ->orWhere('name', 'like', $search);
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(30)
            ->get()
            ->mapWithKeys(fn (Member $member): array => [
                $member->id => "{$member->last_name} {$member->first_name} — {$member->phone}",
            ])
            ->all();
    }

    #[Computed]
    public function selectedMember(): ?Member
    {
        if (! $this->memberId) {
            return null;
        }

        return Member::with(['activeSubscription.plan'])->find($this->memberId);
    }

    #[Computed]
    public function activeSubscription(): ?Subscription
    {
        return $this->selectedMember?->activeSubscription;
    }

    public function updatedMemberId(): void
    {
        unset($this->selectedMember, $this->activeSubscription);
    }

    public function recordCheckIn(): void
    {
        $member = $this->selectedMember;

        if (! $member) {
            Notification::make()->title('Veuillez sélectionner un membre')->warning()->send();

            return;
        }

        try {
            app(CheckInService::class)->recordForSubscriber($member);

            $sub = $member->activeSubscription;
            $infoMsg = $sub?->sessions_remaining !== null
                ? "Séances restantes : {$sub->sessions_remaining}"
                : 'Accès illimité ✅';

            Notification::make()
                ->title("Entrée enregistrée — {$member->name}")
                ->body($infoMsg)
                ->success()
                ->send();

            $this->memberId = null;
            unset($this->selectedMember, $this->activeSubscription);

            // Recharger le reste du dashboard
            $this->dispatch('check-in-recorded');

        } catch (\RuntimeException $e) {
            Notification::make()->title('Accès refusé ❌')->body($e->getMessage())->danger()->persistent()->send();
        }
    }

    public function walkInAction(): Action
    {
        return Action::make('walk_in_quick')
            ->label('Séance du jour')
            ->icon('heroicon-o-bolt')
            ->color('warning')
            ->size('lg')
            ->modalHeading('Client sans abonnement')
            ->modalDescription('Une personne qui paie une seule séance à l’entrée.')
            ->modalWidth('md')
            ->form([
                TextInput::make('last_name')->label('Nom')->required()->maxLength(120),
                TextInput::make('first_name')->label('Prénom')->required()->maxLength(120),
                TextInput::make('phone')->label('Téléphone')->tel()->required()->maxLength(20),
                TextInput::make('amount')->label('Montant (FCFA)')->numeric()->required()->minValue(0)->default(0)->prefix('FCFA'),
                Select::make('payment_method')->label('Mode de paiement')
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()]))
                    ->required()->default(PaymentMethod::Cash->value),
            ])
            ->action(function (array $data): void {
                try {
                    app(CheckInService::class)->recordWalkIn($data);
                    Notification::make()->title('Séance unique enregistrée ✅')->success()->send();
                    $this->dispatch('check-in-recorded');
                } catch (\Exception $e) {
                    Notification::make()->title('Erreur')->body($e->getMessage())->danger()->send();
                }
            });
    }
}
