<?php

namespace App\Filament\Pages;

use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Subscription;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use UnitEnum;

class Reports extends Page
{
    protected static ?string $title = 'Rapports et bilans';

    protected static ?string $navigationLabel = 'Rapports';

    protected static string|UnitEnum|null $navigationGroup = 'Principal';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected string $view = 'filament.pages.reports';

    public string $startDate = '';

    public string $endDate = '';

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::SuperAdmin, UserRole::Admin], true);
    }

    public function mount(): void
    {
        $this->startDate = now()->startOfWeek()->toDateString();
        $this->endDate = now()->endOfWeek()->toDateString();
    }

    public function getHeading(): string
    {
        return 'Rapports et bilans';
    }

    /**
     * @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon}
     */
    protected function periodBounds(): array
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        if ($end->lt($start)) {
            $end = $start->copy()->endOfDay();
        }

        return [$start, $end];
    }

    public function getNewMembersInPeriod(): int
    {
        [$start, $end] = $this->periodBounds();

        return Member::query()->whereBetween('created_at', [$start, $end])->count();
    }

    public function getRevenueInPeriod(): float
    {
        [$start, $end] = $this->periodBounds();

        return (float) Payment::query()
            ->where('status', PaymentStatus::Completed)
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount');
    }

    public function getRenewalRatePercent(): ?float
    {
        [$start, $end] = $this->periodBounds();

        $newSubs = Subscription::query()
            ->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
            ->get(['id', 'member_id', 'start_date']);

        if ($newSubs->isEmpty()) {
            return null;
        }

        $renewals = $newSubs->filter(function (Subscription $sub): bool {
            return Subscription::query()
                ->where('member_id', $sub->member_id)
                ->where('id', '!=', $sub->id)
                ->where('start_date', '<', $sub->start_date)
                ->exists();
        })->count();

        return round(100 * $renewals / $newSubs->count(), 1);
    }

    public function getPaymentsByMethod(): array
    {
        [$start, $end] = $this->periodBounds();

        return Payment::query()
            ->where('status', PaymentStatus::Completed)
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw('payment_method, sum(amount) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method')
            ->toArray();
    }

    /** @return Collection<int, Payment> */
    public function getPaymentDetails(): Collection
    {
        [$start, $end] = $this->periodBounds();

        return Payment::query()
            ->with(['member', 'subscription.plan'])
            ->where('status', PaymentStatus::Completed)
            ->whereBetween('paid_at', [$start, $end])
            ->orderBy('paid_at', 'desc')
            ->get();
    }
}
