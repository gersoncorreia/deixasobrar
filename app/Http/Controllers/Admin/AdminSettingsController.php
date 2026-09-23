<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AdminSettingsController extends Controller
{
    public function index(): Response
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        // Default settings initialization if empty
        $defaultSettings = [
            'landing_headline' => 'Não deixe o mês engolir o seu dinheiro. Descubra seu Teto Diário Seguro.',
            'landing_subheadline' => 'O primeiro SaaS com cálculo reverso de ciclo salarial que blinda suas contas fixas e diz quanto você pode gastar hoje sem culpa.',
            'landing_stat_users' => '14.200+',
            'landing_stat_saved' => 'R$ 4.8M+',
            'landing_stat_ceiling' => 'R$ 52,40',
            'system_default_payday' => '5',
            'system_default_reserve' => '200.00',
            'system_support_email' => 'suporte@deixasobrar.com.br',
            'system_maintenance_mode' => '0',
            'system_allow_new_registrations' => '1',
            'system_gemini_model' => 'gemini-3.6-flash',
            'system_gemini_api_key' => '',
            'asaas_environment' => 'sandbox',
            'asaas_api_key' => '',
            'asaas_webhook_token' => '',
            'plan_free_ocr_limit' => '3',
            'plan_pro_ocr_limit' => '100',
            'plan_free_statement_limit' => '3',
            'enable_ai_statement_classification' => '1',
            'plan_free_ai_statement_limit' => '1',
            'plan_pro_ai_statement_limit' => '20',
        ];

        $settings = [];
        foreach ($defaultSettings as $key => $defaultVal) {
            $settings[$key] = SystemSetting::get($key, $defaultVal);
        }

        // If DB key is empty, check if env has it for convenience
        if (empty($settings['system_gemini_api_key']) && env('GEMINI_API_KEY')) {
            $settings['system_gemini_api_key'] = env('GEMINI_API_KEY');
        }
        if (empty($settings['asaas_api_key']) && env('ASAAS_API_KEY')) {
            $settings['asaas_api_key'] = env('ASAAS_API_KEY');
        }
        if (empty($settings['asaas_webhook_token']) && env('ASAAS_WEBHOOK_TOKEN')) {
            $settings['asaas_webhook_token'] = env('ASAAS_WEBHOOK_TOKEN');
        }

        return Inertia::render('Admin/Settings/Index', [
            'adminUser' => [
                'id' => $currentUser->id,
                'name' => $currentUser->name,
                'email' => $currentUser->email,
            ],
            'settings' => $settings,
            'envHasKey' => !empty(env('GEMINI_API_KEY')),
        ]);
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
        ]);

        foreach ($validated['settings'] as $key => $value) {
            $group = str_starts_with($key, 'landing_') ? 'site' : 'general';
            SystemSetting::set($key, (string) $value, $group);
        }

        $msg = 'Configurações globais salvas com sucesso!';

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    public function testAiConnection(Request $request, \App\Services\VisionOcrService $ocrService): JsonResponse
    {
        $validated = $request->validate([
            'api_key' => ['nullable', 'string'],
            'model' => ['nullable', 'string'],
        ]);

        $result = $ocrService->testConnection($validated['api_key'] ?? null, $validated['model'] ?? null);

        return response()->json($result, 200);
    }

    public function testAsaasConnection(Request $request, \App\Services\AsaasService $asaasService): JsonResponse
    {
        $validated = $request->validate([
            'api_key' => ['nullable', 'string'],
            'environment' => ['nullable', 'string', 'in:sandbox,production'],
        ]);

        $result = $asaasService->testConnection($validated['api_key'] ?? null, $validated['environment'] ?? null);

        return response()->json($result, 200);
    }
}
