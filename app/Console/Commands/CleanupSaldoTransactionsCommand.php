<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\StatementParsers\StatementBalanceSanitizer;
use Illuminate\Console\Command;

class CleanupSaldoTransactionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:cleanup-saldo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove transações importadas indevidamente que contenham SALDO na descrição';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Iniciando varredura de transações contendo SALDO...');

        $transactions = Transaction::all();
        $deletedCount = 0;

        foreach ($transactions as $tx) {
            $isSaldo = StatementBalanceSanitizer::isBalanceRow($tx->description) 
                || ($tx->raw_statement_text && StatementBalanceSanitizer::isBalanceRow($tx->raw_statement_text));

            if ($isSaldo) {
                // Revert account current balance increment
                if ($tx->account) {
                    $tx->account->decrement('current_balance', $tx->amount);
                }
                $tx->delete();
                $deletedCount++;
            }
        }

        $this->info("Varredura concluída! {$deletedCount} transações de SALDO foram removidas e seus saldos revertidos.");

        return Command::SUCCESS;
    }
}
