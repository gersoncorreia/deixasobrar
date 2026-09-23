<?php

namespace Database\Seeders;

use App\Enums\CategoryGroupType;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // Contas Fixas & Essenciais
            [
                'name' => 'Moradia (Aluguel/Condomínio)',
                'group_type' => CategoryGroupType::FixedExpense,
                'icon' => 'home',
                'color_hex' => '#3b82f6',
            ],
            [
                'name' => 'Energia Elétrica & Água',
                'group_type' => CategoryGroupType::FixedExpense,
                'icon' => 'zap',
                'color_hex' => '#0ea5e9',
            ],
            [
                'name' => 'Internet & Celular',
                'group_type' => CategoryGroupType::FixedExpense,
                'icon' => 'wifi',
                'color_hex' => '#0284c7',
            ],
            [
                'name' => 'Saúde & Convênios',
                'group_type' => CategoryGroupType::FixedExpense,
                'icon' => 'heart-pulse',
                'color_hex' => '#06b6d4',
            ],

            // Rotina do Dia a Dia
            [
                'name' => 'Supermercado & Feira',
                'group_type' => CategoryGroupType::RoutineVariable,
                'icon' => 'shopping-cart',
                'color_hex' => '#10b981',
            ],
            [
                'name' => 'Combustível & Mobilidade',
                'group_type' => CategoryGroupType::RoutineVariable,
                'icon' => 'fuel',
                'color_hex' => '#059669',
            ],
            [
                'name' => 'Farmácia & Cuidados',
                'group_type' => CategoryGroupType::RoutineVariable,
                'icon' => 'pill',
                'color_hex' => '#14b8a6',
            ],

            // Estilo de Vida & Lazer
            [
                'name' => 'Delivery & Restaurantes',
                'group_type' => CategoryGroupType::HabitsLifestyle,
                'icon' => 'utensils',
                'color_hex' => '#f59e0b',
            ],
            [
                'name' => 'Assinaturas & Streaming',
                'group_type' => CategoryGroupType::HabitsLifestyle,
                'icon' => 'tv',
                'color_hex' => '#8b5cf6',
            ],
            [
                'name' => 'Lazer & Pequenos Mimos',
                'group_type' => CategoryGroupType::HabitsLifestyle,
                'icon' => 'smile',
                'color_hex' => '#ec4899',
            ],

            // Carnês & Quitações
            [
                'name' => 'Fatura Cartão de Crédito',
                'group_type' => CategoryGroupType::DebtInstallment,
                'icon' => 'credit-card',
                'color_hex' => '#ef4444',
            ],
            [
                'name' => 'Carnês & Financiamentos',
                'group_type' => CategoryGroupType::DebtInstallment,
                'icon' => 'file-text',
                'color_hex' => '#dc2626',
            ],

            // Rendas & Ganhos
            [
                'name' => 'Salário / Pró-labore',
                'group_type' => CategoryGroupType::Income,
                'icon' => 'briefcase',
                'color_hex' => '#10b981',
            ],
            [
                'name' => 'Renda Extra & Freelance',
                'group_type' => CategoryGroupType::Income,
                'icon' => 'trending-up',
                'color_hex' => '#34d399',
            ],
        ];

        foreach ($categories as $data) {
            Category::firstOrCreate(
                ['user_id' => null, 'name' => $data['name']],
                $data
            );
        }
    }
}
