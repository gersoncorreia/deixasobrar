<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Inertia\Inertia;
use Inertia\Response;

class LandingPageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Landing', [
            'stats' => [
                'activeUsers' => SystemSetting::get('landing_stat_users', '14.200+'),
                'totalSaved' => SystemSetting::get('landing_stat_saved', 'R$ 4.8M+'),
                'averageDailyCeiling' => SystemSetting::get('landing_stat_ceiling', 'R$ 52,40'),
            ],
            'hero' => [
                'headline' => SystemSetting::get('landing_headline', 'Não deixe o mês engolir o seu dinheiro. Descubra seu Teto Diário Seguro.'),
                'subheadline' => SystemSetting::get('landing_subheadline', 'O primeiro SaaS com cálculo reverso de ciclo salarial que blinda suas contas fixas e diz quanto você pode gastar hoje sem culpa.'),
            ],
        ]);
    }
}
