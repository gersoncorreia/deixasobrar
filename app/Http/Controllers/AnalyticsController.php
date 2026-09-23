<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FinancialAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function __construct(
        protected FinancialAnalyticsService $analyticsService
    ) {}

    public function index(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->isAdmin() && !session()->has('admin_impersonator_id')) {
            return redirect()->route('admin.dashboard');
        }

        $selectedMonth = $request->query('month');
        $analytics = $this->analyticsService->getAnalyticsData($user, $selectedMonth);

        return Inertia::render('Analytics/Index', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'analytics' => $analytics,
        ]);
    }
}
