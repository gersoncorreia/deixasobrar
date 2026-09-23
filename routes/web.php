<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\StatementController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - DeixaSobrar SaaS
|--------------------------------------------------------------------------
*/

// Public Landing Page (Alta Conversão)
Route::get('/', [LandingPageController::class, 'index'])->name('landing');

// Autenticação Rápida
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Visão Geral / Dashboard (SPA)
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/configuracoes/ciclo', [DashboardController::class, 'updateCycle'])->name('settings.cycle');

// 1. Instituições & Contas Bancárias (CRUD Completo)
Route::get('/contas', [AccountController::class, 'index'])->name('accounts.index');
Route::post('/contas', [AccountController::class, 'store'])->name('accounts.store');
Route::put('/contas/{account}', [AccountController::class, 'update'])->name('accounts.update');
Route::delete('/contas/{account}', [AccountController::class, 'destroy'])->name('accounts.destroy');

// 2. Extratos Bancários (Área Dedicada)
Route::get('/extratos', [StatementController::class, 'index'])->name('statements.index');
Route::post('/extratos/upload', [StatementController::class, 'upload'])->name('statement.upload');

// 3. Lançamentos & Transações (Área Dedicada)
Route::get('/transacoes', [TransactionController::class, 'index'])->name('transactions.index');
Route::post('/transacoes', [TransactionController::class, 'store'])->name('transactions.store');
Route::put('/transacoes/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
Route::delete('/transacoes/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

// 4. Contas Blindadas & Orçamentos (Área Dedicada)
Route::get('/blindagem', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categorias/contas-fixas', [CategoryController::class, 'getFixedBills'])->name('categories.fixed_bills');
Route::post('/categorias', [CategoryController::class, 'store'])->name('categories.store');
Route::put('/categorias/{category}', [CategoryController::class, 'update'])->name('categories.update');
Route::delete('/categorias/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

// 5. Raio-X de Vazamentos & Simulador de Economia (Área Dedicada)
Route::get('/vazamentos', [\App\Http\Controllers\LeakRadarController::class, 'index'])->name('leaks.index');

// 5.1 Central de Inteligência & Análises 360º (Gráficos & Diagnóstico)
Route::get('/analises', [\App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics.index');

// 6. Scanner Nativo OCR de Cupons & Comprovantes Fiscais
Route::get('/scanner', [\App\Http\Controllers\ReceiptScannerController::class, 'index'])->name('scanner.index');
Route::post('/scanner/capture', [\App\Http\Controllers\ReceiptScannerController::class, 'capture'])->name('scanner.capture');
Route::post('/scanner/{receiptScan}/confirm', [\App\Http\Controllers\ReceiptScannerController::class, 'confirm'])->name('scanner.confirm');
Route::post('/scanner/{receiptScan}/reconcile', [\App\Http\Controllers\ReceiptScannerController::class, 'reconcile'])->name('scanner.reconcile');
Route::delete('/scanner/{receiptScan}', [\App\Http\Controllers\ReceiptScannerController::class, 'destroy'])->name('scanner.destroy');

// 7. Planos & Assinaturas (Asaas Checkout)
Route::middleware('auth')->group(function () {
    Route::get('/assinatura', [\App\Http\Controllers\SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('/assinatura/checkout', [\App\Http\Controllers\SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::get('/assinatura/status', [\App\Http\Controllers\SubscriptionController::class, 'checkStatus'])->name('subscription.status');
    Route::post('/assinatura/cancelar', [\App\Http\Controllers\SubscriptionController::class, 'cancel'])->name('subscription.cancel');
});

// 8. Webhook Asaas (Notificações de Pagamento)
Route::post('/api/webhooks/asaas', [\App\Http\Controllers\Webhook\AsaasWebhookController::class, 'handle'])->name('webhook.asaas');
Route::post('/webhooks/asaas', [\App\Http\Controllers\Webhook\AsaasWebhookController::class, 'handle']);

// Retornar da personificação de usuário (Impersonate)
Route::get('/admin/stop-impersonation', function () {
    if ($adminId = session('admin_impersonator_id')) {
        session()->forget('admin_impersonator_id');
        $admin = \App\Models\User::find($adminId);
        if ($admin) {
            \Illuminate\Support\Facades\Auth::login($admin);
            return redirect()->route('admin.users.index')->with('success', 'Você retornou à sua conta Master Admin.');
        }
    }
    return redirect()->route('dashboard');
})->name('admin.stop_impersonation');

// ==========================================
// PAINEL ADMINISTRATIVO MASTER (SUPER ADMIN)
// ==========================================
// Portal de Login Administrativo Isolado (Rate Limiting Estrito: 5 tentativas por minuto)
Route::middleware(['throttle:5,1'])->group(function () {
    Route::get('/admin/login', [\App\Http\Controllers\Admin\AdminAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/admin/login', [\App\Http\Controllers\Admin\AdminAuthController::class, 'login'])->name('admin.login.submit');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Visão Geral Executiva & MRR
    Route::get('/dashboard', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])->name('dashboard');

    // Gestão de Usuários & Assinantes
    Route::get('/usuarios', [\App\Http\Controllers\Admin\AdminUserController::class, 'index'])->name('users.index');
    Route::post('/usuarios/{user}/toggle-status', [\App\Http\Controllers\Admin\AdminUserController::class, 'toggleStatus'])->name('users.toggle_status');
    Route::post('/usuarios/{user}/update-plan', [\App\Http\Controllers\Admin\AdminUserController::class, 'updatePlan'])->name('users.update_plan');
    Route::post('/usuarios/{user}/impersonate', [\App\Http\Controllers\Admin\AdminUserController::class, 'impersonate'])->name('users.impersonate');

    // Configurações do Sistema e do Site
    Route::get('/configuracoes', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'index'])->name('settings.index');
    Route::post('/configuracoes', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'update'])->name('settings.update');
    Route::post('/configuracoes/test-ai', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'testAiConnection'])->name('settings.test_ai');
    Route::post('/configuracoes/test-asaas', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'testAsaasConnection'])->name('settings.test_asaas');
});



