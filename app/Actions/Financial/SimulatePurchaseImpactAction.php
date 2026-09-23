<?php

namespace App\Actions\Financial;

use App\Models\User;

class SimulatePurchaseImpactAction
{
    /**
     * Simulates the impact of a discretionary purchase on Safe-to-Spend and cycle balance.
     *
     * @param array $safeToSpend Current Safe-to-Spend metrics
     * @param float $purchaseAmount Purchase amount in BRL
     * @return array
     */
    public function execute(array $safeToSpend, float $purchaseAmount): array
    {
        $currentCeiling = (float) ($safeToSpend['daily_ceiling'] ?? 0);
        $availableCapital = (float) ($safeToSpend['available_capital'] ?? 0);
        $daysRemaining = max(1, (int) ($safeToSpend['days_remaining'] ?? 1));
        $safetyReserve = (float) ($safeToSpend['safety_reserve'] ?? 0);

        $newAvailableCapital = $availableCapital - $purchaseAmount;
        $newDailyCeiling = $newAvailableCapital > 0 ? ($newAvailableCapital / $daysRemaining) : 0.00;
        $dailyCeilingDelta = $newDailyCeiling - $currentCeiling; // negative

        // Risk status evaluation
        if ($purchaseAmount <= 0) {
            $verdict = 'neutral';
            $verdictLabel = 'Informe um valor';
            $message = 'Digite um valor para simular o impacto no seu orçamento diário.';
        } elseif ($newAvailableCapital < -$safetyReserve) {
            $verdict = 'danger';
            $verdictLabel = 'Invasão Crítica de Contas';
            $message = 'Atenção: Esta compra invadirá suas contas fixas blindadas ou o saldo da sua reserva de emergência!';
        } elseif ($newAvailableCapital < 0) {
            $verdict = 'warning';
            $verdictLabel = 'Uso da Reserva';
            $message = 'Esta compra zerará seu teto diário e consumirá parte da sua reserva de segurança.';
        } elseif ($newDailyCeiling < 25.00) {
            $verdict = 'tight';
            $verdictLabel = 'Orçamento Muito Apertado';
            $message = 'A compra é viável, mas seu teto diário ficará bem reduzido até o dia do pagamento.';
        } else {
            $verdict = 'safe';
            $verdictLabel = 'Compra Tranquila & Segura';
            $message = 'Você pode realizar esta compra com total tranquilidade! Suas contas continuam blindadas.';
        }

        return [
            'purchase_amount' => round($purchaseAmount, 2),
            'current_daily_ceiling' => round($currentCeiling, 2),
            'new_daily_ceiling' => round($newDailyCeiling, 2),
            'daily_ceiling_delta' => round($dailyCeilingDelta, 2),
            'new_available_capital' => round($newAvailableCapital, 2),
            'days_remaining' => $daysRemaining,
            'verdict' => $verdict,
            'verdict_label' => $verdictLabel,
            'message' => $message,
        ];
    }
}
