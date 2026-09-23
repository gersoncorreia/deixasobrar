<?php

namespace App\Services;

use App\Enums\CategoryGroupType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class FinancialAnalyticsService
{
    /**
     * Builds comprehensive 360-degree analytics for the given user and target month.
     *
     * @param User $user
     * @param string|null $month YYYY-MM format, defaults to current month
     * @return array
     */
    public function getAnalyticsData(User $user, ?string $month = null): array
    {
        $currentMonth = $month ? Carbon::createFromFormat('Y-m-d', "{$month}-01") : Carbon::now()->startOfMonth();
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        // 1. Current Month Overview Metrics
        $income = (float) $user->transactions()
            ->whereBetween('transaction_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->where('amount', '>', 0)
            ->sum('amount');

        $expenses = abs((float) $user->transactions()
            ->whereBetween('transaction_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->where('amount', '<', 0)
            ->sum('amount'));

        $leaks = abs((float) $user->transactions()
            ->whereBetween('transaction_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->where('amount', '<', 0)
            ->where('is_leak', true)
            ->sum('amount'));

        $netSavings = $income - $expenses;
        $savingsRate = $income > 0 ? round(($netSavings / $income) * 100, 1) : 0.0;

        // Daily Average Expense
        $daysInPeriod = min($endOfMonth->day, Carbon::now()->isSameMonth($currentMonth) ? Carbon::now()->day : $endOfMonth->day);
        $daysInPeriod = max(1, $daysInPeriod);
        $dailyAverageSpent = round($expenses / $daysInPeriod, 2);

        // 2. Health Score Calculation (0 to 100)
        $healthScore = $this->calculateHealthScore($income, $expenses, $leaks, $savingsRate);

        // 3. Last 6 Months History (Cash Flow Evolution)
        $sixMonthsHistory = $this->getSixMonthsHistory($user, $currentMonth);

        // 4. Spending Distribution by Methodology Group
        $groupDistribution = $this->getGroupDistribution($user, $startOfMonth, $endOfMonth, $expenses);

        // 5. Top 5 Financial Villains / Budget Drains
        $topVillains = $this->getTopVillains($user, $startOfMonth, $endOfMonth);

        // 6. Cesta de Compras & Produtos de Cupons OCR (Micro-análise de consumo)
        $basketAnalytics = $this->getBasketAnalytics($user, $startOfMonth, $endOfMonth);

        // 7. Available Months for Filtering (Cross-database compatible: MySQL, SQLite, PostgreSQL)
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        $monthExpr = $driver === 'sqlite' 
            ? "strftime('%Y-%m', transaction_date)" 
            : "DATE_FORMAT(transaction_date, '%Y-%m')";

        $availableMonths = $user->transactions()
            ->selectRaw("DISTINCT {$monthExpr} as month")
            ->orderBy('month', 'desc')
            ->pluck('month')
            ->filter()
            ->values()
            ->toArray();

        if (!in_array($currentMonth->format('Y-m'), $availableMonths)) {
            array_unshift($availableMonths, $currentMonth->format('Y-m'));
        }

        return [
            'selected_month' => $currentMonth->format('Y-m'),
            'selected_month_label' => $this->formatMonthLabel($currentMonth->format('Y-m')),
            'available_months' => $availableMonths,
            'metrics' => [
                'income' => $income,
                'expenses' => $expenses,
                'net_savings' => $netSavings,
                'savings_rate' => $savingsRate,
                'leaks' => $leaks,
                'leaks_percentage' => $expenses > 0 ? round(($leaks / $expenses) * 100, 1) : 0.0,
                'daily_average_spent' => $dailyAverageSpent,
                'total_balance' => (float) $user->accounts()->sum('current_balance'),
            ],
            'health_score' => $healthScore,
            'six_months_history' => $sixMonthsHistory,
            'group_distribution' => $groupDistribution,
            'top_villains' => $topVillains,
            'basket_analytics' => $basketAnalytics,
        ];
    }

    /**
     * Calculates transparent Health Score (0-100) with intuitive human verdict.
     */
    protected function calculateHealthScore(float $income, float $expenses, float $leaks, float $savingsRate): array
    {
        if ($income <= 0 && $expenses <= 0) {
            return [
                'score' => 50,
                'status' => 'neutro',
                'badge' => 'Sem Dados Suficientes',
                'color' => '#94a3b8',
                'title' => 'Comece importando seus extratos',
                'advice' => 'Importe seu primeiro extrato ou cadastre suas contas para ver o diagnóstico completo da sua vida financeira.',
            ];
        }

        $score = 50; // base

        // Savings Rate Weight (up to +35 or -35)
        if ($savingsRate >= 20) {
            $score += 35;
        } elseif ($savingsRate >= 10) {
            $score += 25;
        } elseif ($savingsRate > 0) {
            $score += 15;
        } elseif ($savingsRate > -15) {
            $score -= 15;
        } else {
            $score -= 35;
        }

        // Leaks Weight (penalize if leaks exceed 8% of expenses)
        $leakRatio = $expenses > 0 ? ($leaks / $expenses) : 0;
        if ($leakRatio <= 0.02) {
            $score += 15;
        } elseif ($leakRatio <= 0.08) {
            $score += 5;
        } elseif ($leakRatio <= 0.20) {
            $score -= 10;
        } else {
            $score -= 20;
        }

        $score = max(5, min(100, $score));

        if ($score >= 80) {
            return [
                'score' => $score,
                'status' => 'excelente',
                'badge' => 'Excelente Saúde Financeira',
                'color' => '#10b981', // emerald
                'title' => 'Sua vida financeira está respirando com folga!',
                'advice' => 'Você está conseguindo fazer o dinheiro sobrar e mantendo os vazamentos sob total controle. Continue assim para construir sua reserva sólida.',
            ];
        } elseif ($score >= 60) {
            return [
                'score' => $score,
                'status' => 'bom',
                'badge' => 'Equilíbrio Estável',
                'color' => '#3b82f6', // blue
                'title' => 'Você está no caminho certo, com pequenas sobras.',
                'advice' => 'Seus ganhos cobrem suas contas, mas alguns pequenos gastos supérfluos ainda diminuem seu potencial de poupança no fim do mês.',
            ];
        } elseif ($score >= 40) {
            return [
                'score' => $score,
                'status' => 'alerta',
                'badge' => 'Sinal de Atenção',
                'color' => '#f59e0b', // amber
                'title' => 'O mês está engolindo quase tudo o que você ganha.',
                'advice' => 'Você está operando muito próximo do limite. Cortar os vazamentos identificados pelo Raio-X pode ser o segredo para você voltar a respirar aliviado.',
            ];
        } else {
            return [
                'score' => $score,
                'status' => 'critico',
                'badge' => 'Risco de Endividamento',
                'color' => '#f43f5e', // rose
                'title' => 'Cuidado: Seus gastos ultrapassaram sua renda.',
                'advice' => 'Você gastou mais do que ganhou neste período. Priorize suas contas blindadas essenciais e congele qualquer compra impulsiva imediatamente.',
            ];
        }
    }

    /**
     * Last 6 months cash flow data for line/bar charts.
     */
    protected function getSixMonthsHistory(User $user, Carbon $referenceMonth): array
    {
        $months = [];
        $incomeData = [];
        $expenseData = [];
        $surplusData = [];

        for ($i = 5; $i >= 0; $i--) {
            $target = $referenceMonth->copy()->subMonths($i);
            $mStart = $target->copy()->startOfMonth()->format('Y-m-d');
            $mEnd = $target->copy()->endOfMonth()->format('Y-m-d');

            $mIncome = (float) $user->transactions()
                ->whereBetween('transaction_date', [$mStart, $mEnd])
                ->where('amount', '>', 0)
                ->sum('amount');

            $mExpense = abs((float) $user->transactions()
                ->whereBetween('transaction_date', [$mStart, $mEnd])
                ->where('amount', '<', 0)
                ->sum('amount'));

            $mSurplus = $mIncome - $mExpense;

            $months[] = $this->formatMonthLabel($target->format('Y-m'), true);
            $incomeData[] = round($mIncome, 2);
            $expenseData[] = round($mExpense, 2);
            $surplusData[] = round($mSurplus, 2);
        }

        return [
            'labels' => $months,
            'income' => $incomeData,
            'expense' => $expenseData,
            'surplus' => $surplusData,
        ];
    }

    /**
     * Spending grouped by DeixaSobrar methodology.
     */
    protected function getGroupDistribution(User $user, Carbon $start, Carbon $end, float $totalExpenses): array
    {
        $transactions = $user->transactions()
            ->with('category')
            ->whereBetween('transaction_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->where('amount', '<', 0)
            ->get();

        $groups = [
            'fixed_expense' => ['label' => 'Contas Blindadas (Fixas)', 'color' => '#3b82f6', 'total' => 0.0],
            'routine_variable' => ['label' => 'Rotina do Mês (Mercado/Transporte)', 'color' => '#10b981', 'total' => 0.0],
            'habits_lifestyle' => ['label' => 'Lazer & Estilo de Vida', 'color' => '#8b5cf6', 'total' => 0.0],
            'leaks' => ['label' => 'Vazamentos & Drenos Ocultos', 'color' => '#f59e0b', 'total' => 0.0],
            'other' => ['label' => 'Outros / Sem Categoria', 'color' => '#64748b', 'total' => 0.0],
        ];

        foreach ($transactions as $tx) {
            $val = abs((float) $tx->amount);

            if ($tx->is_leak) {
                $groups['leaks']['total'] += $val;
                continue;
            }

            $cat = $tx->category;
            if (!$cat) {
                $groups['other']['total'] += $val;
                continue;
            }

            $groupKey = $cat->group_type instanceof CategoryGroupType ? $cat->group_type->value : ($cat->group_type ?? 'other');
            if (isset($groups[$groupKey])) {
                $groups[$groupKey]['total'] += $val;
            } else {
                $groups['other']['total'] += $val;
            }
        }

        $result = [];
        foreach ($groups as $key => $info) {
            $pct = $totalExpenses > 0 ? round(($info['total'] / $totalExpenses) * 100, 1) : 0.0;
            $result[] = [
                'key' => $key,
                'label' => $info['label'],
                'color' => $info['color'],
                'total' => round($info['total'], 2),
                'percentage' => $pct,
            ];
        }

        return $result;
    }

    /**
     * Top 5 expense drains/villains in the period.
     */
    protected function getTopVillains(User $user, Carbon $start, Carbon $end): array
    {
        return $user->transactions()
            ->selectRaw("description, COUNT(*) as count, SUM(ABS(amount)) as total_spent, MAX(is_leak) as is_leak, MAX(leak_reason) as leak_reason")
            ->whereBetween('transaction_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->where('amount', '<', 0)
            ->groupBy('description')
            ->orderByDesc('total_spent')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'description' => $item->description,
                    'count' => (int) $item->count,
                    'total_spent' => round((float) $item->total_spent, 2),
                    'is_leak' => (bool) $item->is_leak,
                    'leak_reason' => $item->leak_reason,
                ];
            })
            ->toArray();
    }

    /**
     * Micro-analytics for OCR receipts: items purchased, top products, category split.
     */
    protected function getBasketAnalytics(User $user, Carbon $start, Carbon $end): array
    {
        $scanIds = \App\Models\ReceiptScan::where('user_id', $user->id)
            ->whereBetween('purchased_at', [$start->format('Y-m-d 00:00:00'), $end->format('Y-m-d 23:59:59')])
            ->pluck('id');

        $totalScans = $scanIds->count();
        if ($totalScans === 0) {
            return [
                'total_scans' => 0,
                'total_spent' => 0.0,
                'total_items_count' => 0,
                'top_products' => [],
                'category_split' => [],
            ];
        }

        $totalSpent = (float) \App\Models\ReceiptScan::whereIn('id', $scanIds)->sum('total_amount');
        $totalItemsCount = (int) \App\Models\ReceiptItem::whereIn('receipt_scan_id', $scanIds)->sum('quantity');

        // Top 10 most purchased products by total value
        $topProducts = \App\Models\ReceiptItem::whereIn('receipt_scan_id', $scanIds)
            ->selectRaw("item_name, SUM(quantity) as total_qty, SUM(total_price) as total_spent, MAX(item_category) as category")
            ->groupBy('item_name')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->item_name,
                    'product_name' => $item->item_name,
                    'quantity' => (float) $item->total_qty,
                    'total_qty' => (float) $item->total_qty,
                    'total_spent' => round((float) $item->total_spent, 2),
                    'category' => $item->category,
                ];
            })
            ->toArray();

        // Category split inside receipt items
        $categoryTotals = \App\Models\ReceiptItem::whereIn('receipt_scan_id', $scanIds)
            ->selectRaw("item_category, COUNT(*) as count, SUM(total_price) as total_spent")
            ->groupBy('item_category')
            ->orderByDesc('total_spent')
            ->get();

        $categoryLabels = [
            'alimentacao_essencial' => ['label' => 'Alimentação & Essencial', 'color' => '#10b981'],
            'limpeza' => ['label' => 'Limpeza & Higiene', 'color' => '#3b82f6'],
            'superfluo' => ['label' => 'Supérfluo / Impulso', 'color' => '#f59e0b'],
            'bebidas' => ['label' => 'Bebidas & Lazer', 'color' => '#8b5cf6'],
            'outros' => ['label' => 'Outros', 'color' => '#64748b'],
        ];

        $categorySplit = [];
        $itemsGrandTotal = $categoryTotals->sum('total_spent');

        foreach ($categoryTotals as $cat) {
            $catKey = $cat->item_category ?? 'outros';
            $info = $categoryLabels[$catKey] ?? ['label' => ucfirst($catKey), 'color' => '#94a3b8'];
            $val = (float) $cat->total_spent;
            $pct = $itemsGrandTotal > 0 ? round(($val / $itemsGrandTotal) * 100, 1) : 0.0;

            $categorySplit[] = [
                'key' => $catKey,
                'label' => $info['label'],
                'color' => $info['color'],
                'count' => (int) $cat->count,
                'total' => round($val, 2),
                'percentage' => $pct,
            ];
        }

        return [
            'total_scans' => $totalScans,
            'total_spent' => round($totalSpent, 2),
            'total_items_count' => $totalItemsCount,
            'top_products' => $topProducts,
            'category_split' => $categorySplit,
        ];
    }

    protected function formatMonthLabel(string $ym, bool $short = false): string
    {
        $parts = explode('-', $ym);
        if (count($parts) < 2) return $ym;
        $months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $shortMonths = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        $mIndex = (int) $parts[1] - 1;

        if ($short) {
            return ($shortMonths[$mIndex] ?? '') . '/' . substr($parts[0], 2);
        }

        return ($months[$mIndex] ?? '') . ' de ' . $parts[0];
    }
}
