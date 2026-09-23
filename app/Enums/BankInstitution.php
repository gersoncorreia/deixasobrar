<?php

namespace App\Enums;

enum BankInstitution: string
{
    case BancoDoBrasil = 'banco_do_brasil';
    case Nubank = 'nubank';
    case Itau = 'itau';
    case Bradesco = 'bradesco';
    case Santander = 'santander';
    case Inter = 'inter';
    case Caixa = 'caixa';
    case C6 = 'c6';
    case Generic = 'generic';

    public function label(): string
    {
        return match ($this) {
            self::BancoDoBrasil => 'Banco do Brasil',
            self::Nubank => 'Nubank',
            self::Itau => 'Itaú Unibanco',
            self::Bradesco => 'Bradesco',
            self::Santander => 'Santander',
            self::Inter => 'Banco Inter',
            self::Caixa => 'Caixa Econômica',
            self::C6 => 'C6 Bank',
            self::Generic => 'Outro / Genérico',
        };
    }
}
