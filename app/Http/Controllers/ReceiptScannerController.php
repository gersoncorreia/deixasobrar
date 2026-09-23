<?php

namespace App\Http\Controllers;

use App\Actions\Receipts\ProcessReceiptScanAction;
use App\Actions\Receipts\ReconcileReceiptWithStatementAction;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\ReceiptScan;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ReceiptScannerController extends Controller
{
    public function __construct(
        protected ProcessReceiptScanAction $processScan,
        protected ReconcileReceiptWithStatementAction $reconcileAction,
        protected \App\Services\PlanQuotaService $quotaService
    ) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = Auth::user() ?? User::first();

        $scans = ReceiptScan::where('user_id', $user->id)
            ->with(['items', 'transaction'])
            ->orderBy('id', 'desc')
            ->paginate(15);

        $accounts = $user->accounts()->get();
        $categories = Category::where('user_id', $user->id)
            ->orWhereNull('user_id')
            ->get();

        $quota = $this->quotaService->checkOcrQuota($user);

        return Inertia::render('Scanner/Index', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'scans' => $scans,
            'accounts' => $accounts,
            'categories' => $categories,
            'quota' => $quota,
        ]);
    }

    public function capture(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['nullable', 'image', 'max:12288'], // 12MB
            'camera_data' => ['nullable', 'string'],
            'scan_type' => ['nullable', 'string', 'in:paper_ocr,pix_receipt,nfce_qrcode,manual_photo'],
        ]);

        /** @var User $user */
        $user = Auth::user() ?? User::first();

        // 1. Quota Check
        $quota = $this->quotaService->checkOcrQuota($user);
        if (!$quota['allowed']) {
            $msg = $quota['upgrade_required']
                ? "Você atingiu o limite de {$quota['limit']} leituras de comprovantes por mês do Plano Gratuito. Faça upgrade para o Plano Pro para ter leituras de IA ampliadas!"
                : "Você atingiu o limite mensal de {$quota['limit']} leituras de IA deste ciclo.";

            return response()->json([
                'success' => false,
                'message' => $msg,
                'quota_exceeded' => true,
                'quota' => $quota,
            ], 403);
        }

        $fileOrData = $request->file('image') ?: $request->input('camera_data');

        if (!$fileOrData) {
            return response()->json(['success' => false, 'message' => 'Nenhuma imagem fornecida.'], 422);
        }

        $scan = $this->processScan->execute(
            $user, 
            $fileOrData, 
            $request->input('scan_type', 'paper_ocr')
        );

        // Find match candidates with statement transactions
        $candidates = $this->reconcileAction->findCandidates($scan);

        $updatedQuota = $this->quotaService->checkOcrQuota($user);

        return response()->json([
            'success' => true,
            'message' => 'Comprovante capturado e analisado com sucesso!',
            'scan' => $scan->load('items'),
            'quota' => $updatedQuota,
            'match_candidates' => $candidates,
        ]);
    }

    public function confirm(Request $request, ReceiptScan $receiptScan): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user() ?? User::first();

        if ($receiptScan->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['required', 'date'],
        ]);

        $account = Account::findOrFail($validated['account_id']);

        // Create transaction
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $validated['category_id'] ?? null,
            'transaction_date' => Carbon::parse($validated['transaction_date'])->format('Y-m-d'),
            'description' => $validated['description'],
            'amount' => -abs((float) $validated['amount']),
            'type' => 'expense',
            'status' => 'confirmed',
        ]);

        // Decrement account balance
        $account->decrement('current_balance', abs((float) $validated['amount']));

        // Link scan
        $receiptScan->update([
            'transaction_id' => $transaction->id,
            'match_status' => 'manual_created',
        ]);

        $msg = 'Lançamento confirmado e saldo da conta atualizado!';

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg, 'transaction' => $transaction]);
        }

        return back()->with('success', $msg);
    }

    public function reconcile(Request $request, ReceiptScan $receiptScan): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user() ?? User::first();

        if ($receiptScan->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'transaction_id' => ['required', 'exists:transactions,id'],
        ]);

        $transaction = Transaction::findOrFail($validated['transaction_id']);

        $matched = $this->reconcileAction->match($receiptScan, $transaction);

        if (!$matched) {
            return response()->json(['success' => false, 'message' => 'Não foi possível vincular este comprovante.'], 422);
        }

        $msg = 'Comprovante conciliado com o extrato bancário com sucesso!';

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    public function destroy(ReceiptScan $receiptScan): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = Auth::user() ?? User::first();

        if ($receiptScan->user_id !== $user->id) {
            abort(403);
        }

        $receiptScan->delete();

        $msg = 'Comprovante removido com sucesso!';

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }
}
