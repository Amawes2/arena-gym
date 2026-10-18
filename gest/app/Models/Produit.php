<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentGym;
use App\Support\EliteFitGym;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Produit extends Model
{
    use BelongsToCurrentGym;

    /** @var list<string> */
    protected $fillable = [
        'gym_id',
        'nom',
        'slug',
        'description',
        'prix',
        'stock',
        'categorie',
        'image',
        'actif',
        'featured',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'prix' => 'decimal:2',
            'stock' => 'integer',
            'actif' => 'boolean',
            'featured' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Produit $produit): void {
            if (empty($produit->gym_id)) {
                $produit->gym_id = EliteFitGym::id();
            }

            if (empty($produit->slug)) {
                $produit->slug = Str::slug($produit->nom);
            }
        });
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(CommandeLigne::class);
    }

    /**
     * @param  Builder<Produit>  $query
     * @return Builder<Produit>
     */
    public function scopeActif($query): Builder
    {
        return $query->where('actif', true);
    }

    /**
     * @param  Builder<Produit>  $query
     * @return Builder<Produit>
     */
    public function scopeForEliteFit($query): Builder
    {
        return $query->where('gym_id', EliteFitGym::id());
    }
}
