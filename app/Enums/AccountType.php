<?php

namespace App\Enums;

enum AccountType: string
{
    case Checking = 'checking';
    case Savings = 'savings';
    case CreditCard = 'credit_card';
    case Cash = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::Checking => 'Conta Corrente',
            self::Savings => 'Poupança / Reserva',
            self::CreditCard => 'Cartão de Crédito',
            self::Cash => 'Dinheiro em Mãos',
        };
    }
}
