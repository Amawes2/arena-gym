<?php

namespace App\Filament\Resources\Subscriptions;

use App\Enums\CheckInType;
use App\Enums\ClientType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Members\Schemas\MemberForm;
use App\Filament\Resources\Subscriptions\Pages\CreateSubscription;
use App\Filament\Resources\Subscriptions\Pages\EditMemberSubscription;
use App\Filament\Resources\Subscriptions\Pages\ListSubscriptions;
use App\Filament\Resources\Subscriptions\Pages\ViewMemberProfile;
use App\Filament\Resources\Subscriptions\Tables\SubscriptionsTable;
use App\Models\CheckIn;
use App\Models\Member;
use App\Models\Payment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class SubscriptionResource extends Resource
{
    /**
     * Ce resource pilote les membres (abonnés + walk-in) depuis une seule vue.
     */
    protected static ?string $model = Member::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Clients';

    protected static ?string $modelLabel = 'client';

    protected static ?string $pluralModelLabel = 'clients';

    protected static string|UnitEnum|null $navigationGroup = 'Principal';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'last_name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['last_name', 'first_name', 'phone', 'name'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return "{$record->name} — {$record->phone}";
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Forfait' => $record->activeSubscription?->plan?->name ?? '—',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return MemberForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubscriptionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptions::route('/'),
            'create' => CreateSubscription::route('/create'),
            'view' => ViewMemberProfile::route('/{record}'),
            'edit' => EditMemberSubscription::route('/{record}/edit'),
        ];
    }

    /**
     * Enregistre un walk-in : crée le membre, un check-in et un paiement en bloc.
     *
     * @param  array{first_name: string, last_name: string, phone: string, amount: float, payment_method: string}  $data
     */
    public static function registerWalkIn(array $data): Member
    {
        $member = Member::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'client_type' => ClientType::WalkIn,
        ]);

        $checkIn = CheckIn::create([
            'member_id' => $member->id,
            'subscription_id' => null,
            'type' => CheckInType::WalkIn,
            'checked_in_at' => now(),
        ]);

        Payment::create([
            'member_id' => $member->id,
            'subscription_id' => null,
            'amount' => $data['amount'] ?? 0,
            'payment_method' => $data['payment_method'] ?? PaymentMethod::Cash->value,
            'status' => PaymentStatus::Completed,
            'paid_at' => now(),
        ]);

        return $member;
    }
}
