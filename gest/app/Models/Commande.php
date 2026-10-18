<?php

namespace App\Models;

use App\Enums\CommandeStatut;
use App\Models\Concerns\BelongsToCurrentGym;
use App\Support\EliteFitGym;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commande extends Model
{
    use BelongsToCurrentGym;

    /** @var list<string> */
    protected $fillable = [
        'gym_id',
        'member_id',
        'numero',
        'nom_client',
        'telephone_client',
        'montant_total',
        'statut',
        'mode_paiement',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'montant_total' => 'decimal:2',
            'statut' => CommandeStatut::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Commande $commande): void {
            if (empty($commande->gym_id)) {
                $commande->gym_id = EliteFitGym::id();
            }
        });
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(CommandeLigne::class);
    }
}
