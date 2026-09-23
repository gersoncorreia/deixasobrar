<?php

namespace App\Enums;

enum CategoryGroupType: string
{
    case FixedExpense = 'fixed_expense';
    case RoutineVariable = 'routine_variable';
    case HabitsLifestyle = 'habits_lifestyle';
    case DebtInstallment = 'debt_installment';
    case Income = 'income';

    public function label(): string
    {
        return match ($this) {
            self::FixedExpense => 'Contas Fixas & Essenciais',
            self::RoutineVariable => 'Rotina do Dia a Dia',
            self::HabitsLifestyle => 'Estilo de Vida & Lazer',
            self::DebtInstallment => 'Carnês & Quitações',
            self::Income => 'Rendas & Ganhos',
        };
    }
}
