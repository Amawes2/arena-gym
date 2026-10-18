<?php

namespace App\Enums;

enum CommandeStatut: string
{
    case EnAttente = 'en_attente';
    case Confirmee = 'confirmee';
    case EnCours = 'en_cours';
    case Livree = 'livree';
    case Annulee = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente',
            self::Confirmee => 'Confirmée',
            self::EnCours => 'En cours',
            self::Livree => 'Livrée',
            self::Annulee => 'Annulée',
        };
    }
}
