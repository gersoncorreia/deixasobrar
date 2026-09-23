<?php

namespace App\Enums;

enum SubscriptionPlan: string
{
    case Free = 'free';
    case ProMonthly = 'pro_mensal';
    case ProAnnual = 'pro_anual';
    case Family = 'familia';

    public function title(): string
    {
        return match ($this) {
            self::Free => 'Gratuito',
            self::ProMonthly => 'Pro Mensal',
            self::ProAnnual => 'Pro Anual',
            self::Family => 'Plano Família',
        };
    }

    public function priceFormatted(): string
    {
        return match ($this) {
            self::Free => 'R$ 0',
            self::ProMonthly => 'R$ 19,90',
            self::ProAnnual => 'R$ 179,90',
            self::Family => 'R$ 29,90',
        };
    }
}
