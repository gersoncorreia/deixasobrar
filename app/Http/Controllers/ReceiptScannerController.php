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

        $merchantFilter = $request->query('merchant');
        $searchQuery = $request->query('search');

        $scansQuery = ReceiptScan::where('user_id', $user->id)
            ->with(['items', 'transaction'])
            ->orderBy('id', 'desc');

        if (!empty($merchantFilter)) {
            $scansQuery->where('merchant_name', $merchantFilter);
        }

        if (!empty($searchQuery)) {
            $scansQuery->where(function ($q) use ($searchQuery) {
                $q->where('merchant_name', 'like', "%{$searchQuery}%")
                  ->orWhere('merchant_tax_id', 'like', "%{$searchQuery}%")
                  ->orWhereHas('items', function ($itemQ) use ($searchQuery) {
                      $itemQ->where('item_name', 'like', "%{$searchQuery}%");
                  });
            });
        }

        $scans = $scansQuery->paginate(15)->withQueryString();

        $accounts = $user->accounts()->get();
        $categories = Category::where('user_id', $user->id)
            ->orWhereNull('user_id')
            ->get();

        $quota = $this->quotaService->checkOcrQuota($user);

        // Lista de estabelecimentos únicos do usuário com contagem e total gasto
        $merchants = ReceiptScan::where('user_id', $user->id)
            ->whereNotNull('merchant_name')
            ->selectRaw("merchant_name, MAX(merchant_tax_id) as cnpj, COUNT(*) as total_scans, SUM(total_amount) as total_spent, MAX(purchased_at) as last_purchase_at")
            ->groupBy('merchant_name')
            ->orderByDesc('total_spent')
            ->get()
            ->map(function ($m) {
                return [
                    'merchant_name' => $m->merchant_name,
                    'cnpj' => $m->cnpj,
                    'total_scans' => (int) $m->total_scans,
                    'total_spent' => round((float) $m->total_spent, 2),
                    'last_purchase_at' => $m->last_purchase_at,
                ];
            });

        // Agregação dos produtos mais comprados em cupons para análise do usuário
        $topProductsQuery = \App\Models\ReceiptItem::whereHas('receiptScan', function ($q) use ($user, $merchantFilter) {
                $q->where('user_id', $user->id);
                if (!empty($merchantFilter)) {
                    $q->where('merchant_name', $merchantFilter);
                }
            });

        if (!empty($searchQuery)) {
            $topProductsQuery->where('item_name', 'like', "%{$searchQuery}%");
        }

        $topProducts = $topProductsQuery
            ->selectRaw("item_name, SUM(quantity) as total_qty, SUM(total_price) as total_spent, COUNT(*) as occurrences, MAX(item_category) as category")
            ->groupBy('item_name')
            ->orderByDesc('total_spent')
            ->limit(30)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->item_name,
                    'quantity' => (float) $item->total_qty,
                    'total_spent' => round((float) $item->total_spent, 2),
                    'occurrences' => (int) $item->occurrences,
                    'category' => $item->category,
                ];
            });

        // Radar Comparador de Preços: produtos comprados em diferentes notas com variação de preços
        $priceComparison = \App\Models\ReceiptItem::join('receipt_scans', 'receipt_items.receipt_scan_id', '=', 'receipt_scans.id')
            ->where('receipt_scans.user_id', $user->id)
            ->where('receipt_items.unit_price', '>', 0)
            ->select([
                'receipt_items.item_name',
                'receipt_items.unit_price',
                'receipt_items.unit',
                'receipt_scans.merchant_name',
                'receipt_scans.purchased_at',
            ])
            ->orderBy('receipt_items.item_name')
            ->orderBy('receipt_items.unit_price')
            ->get()
            ->groupBy('item_name')
            ->map(function ($group, $itemName) {
                $prices = $group->pluck('unit_price')->unique()->values();
                $minEntry = $group->sortBy('unit_price')->first();
                $maxEntry = $group->sortByDesc('unit_price')->first();
                $merchantsCount = $group->pluck('merchant_name')->unique()->count();

                $history = $group->map(function ($entry) {
                    return [
                        'merchant' => $entry->merchant_name ?: 'Estabelecimento',
                        'price' => (float) $entry->unit_price,
                        'unit' => $entry->unit ?: 'UN',
                        'date' => $entry->purchased_at ? \Carbon\Carbon::parse($entry->purchased_at)->format('d/m/Y') : null,
                    ];
                })->values();

                $minPrice = (float) $minEntry->unit_price;
                $maxPrice = (float) $maxEntry->unit_price;
                $diff = round($maxPrice - $minPrice, 2);

                return [
                    'item_name' => $itemName,
                    'unit' => $minEntry->unit ?: 'UN',
                    'min_price' => $minPrice,
                    'best_merchant' => $minEntry->merchant_name ?: 'Estabelecimento',
                    'max_price' => $maxPrice,
                    'highest_merchant' => $maxEntry->merchant_name ?: 'Estabelecimento',
                    'difference' => $diff,
                    'savings_percentage' => $maxPrice > 0 ? round(($diff / $maxPrice) * 100, 1) : 0,
                    'merchants_count' => $merchantsCount,
                    'total_records' => $group->count(),
                    'history' => $history,
                ];
            })
            ->values()
            ->sortByDesc('difference')
            ->values();

        $totalScansCount = ReceiptScan::where('user_id', $user->id)->count();
        $totalItemsCount = (int) \App\Models\ReceiptItem::whereHas('receiptScan', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->sum('quantity');

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
            'topProducts' => $topProducts,
            'merchants' => $merchants,
            'priceComparison' => $priceComparison,
            'filters' => [
                'merchant' => $merchantFilter,
                'search' => $searchQuery,
            ],
            'totalScansCount' => $totalScansCount,
            'totalItemsCount' => $totalItemsCount,
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

        // Se o account_id veio vazio ou não fornecido, seleciona automaticamente a primeira conta ativa do usuário
        if (empty($request->input('account_id'))) {
            $firstAccount = $user->accounts()->first();
            if ($firstAccount) {
                $request->merge(['account_id' => $firstAccount->id]);
            }
        }

        $validated = $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['required', 'date'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['nullable', 'numeric'],
            'items.*.total_price' => ['required', 'numeric'],
            'items.*.item_category' => ['nullable', 'string'],
        ], [
            'account_id.required' => 'Por favor, selecione qual conta bancária debitar esta compra.',
            'account_id.exists' => 'A conta bancária selecionada não foi encontrada.',
            'amount.min' => 'O valor do comprovante deve ser maior que zero.',
        ]);

        $account = Account::findOrFail($validated['account_id']);

        // Update items if modified in review modal
        if (!empty($validated['items'])) {
            $updatedItemIds = [];
            foreach ($validated['items'] as $itemData) {
                if (!empty($itemData['id'])) {
                    $item = \App\Models\ReceiptItem::where('receipt_scan_id', $receiptScan->id)
                        ->where('id', $itemData['id'])
                        ->first();
                    if ($item) {
                        $item->update([
                            'item_name' => $itemData['item_name'],
                            'quantity' => (float) $itemData['quantity'],
                            'unit_price' => (float) ($itemData['unit_price'] ?? 0.0),
                            'total_price' => (float) $itemData['total_price'],
                            'item_category' => $itemData['item_category'] ?? 'alimentacao_essencial',
                        ]);
                        $updatedItemIds[] = $item->id;
                    }
                } else {
                    $newItem = \App\Models\ReceiptItem::create([
                        'receipt_scan_id' => $receiptScan->id,
                        'item_name' => $itemData['item_name'],
                        'quantity' => (float) $itemData['quantity'],
                        'unit' => 'UN',
                        'unit_price' => (float) ($itemData['unit_price'] ?? 0.0),
                        'total_price' => (float) $itemData['total_price'],
                        'item_category' => $itemData['item_category'] ?? 'alimentacao_essencial',
                    ]);
                    $updatedItemIds[] = $newItem->id;
                }
            }
        }

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

        // Link scan & update total amount
        $receiptScan->update([
            'total_amount' => abs((float) $validated['amount']),
            'merchant_name' => $validated['description'],
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

        \Illuminate\Support\Facades\DB::transaction(function () use ($receiptScan) {
            // Se o comprovante gerou uma despesa na conta bancária, reverte o saldo e apaga a transação
            if ($receiptScan->transaction) {
                $tx = $receiptScan->transaction;
                $account = $tx->account;
                if ($account) {
                    $account->increment('current_balance', abs((float) $tx->amount));
                }
                $tx->delete();
            }

            $receiptScan->delete();
        });

        $msg = 'Comprovante removido com sucesso e saldo revertido!';

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }
}
