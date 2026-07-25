<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Dashboard\DashboardDataService;

class DashboardController extends Controller
{
    public function __construct(private DashboardDataService $dashboardData)
    {
    }

    public function __invoke()
    {
        $user = auth()->user();

        if ($user->hasRole('technician') && ! $user->hasAnyRole(['super-admin', 'admin'])) {
            return view('dashboard-technician', $this->dashboardData->technicianData($user));
        }

        if ($user->hasRole('finance') && ! $user->hasAnyRole(['super-admin', 'admin'])) {
            return view('dashboard-finance', $this->dashboardData->financialData());
        }

        return view('dashboard', array_merge($this->dashboardData->financialData(), $this->dashboardData->customerData()));
    }
}
