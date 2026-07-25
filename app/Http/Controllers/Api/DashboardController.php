<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardDataService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private DashboardDataService $dashboardData)
    {
    }

    public function __invoke(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('technician') && ! $user->hasAnyRole(['super-admin', 'admin'])) {
            return response()->json([
                'type' => 'technician',
                'data' => $this->dashboardData->technicianData($user),
            ]);
        }

        if ($user->hasRole('finance') && ! $user->hasAnyRole(['super-admin', 'admin'])) {
            return response()->json([
                'type' => 'finance',
                'data' => $this->dashboardData->financialData(),
            ]);
        }

        return response()->json([
            'type' => 'admin',
            'data' => array_merge($this->dashboardData->financialData(), $this->dashboardData->customerData()),
        ]);
    }
}
