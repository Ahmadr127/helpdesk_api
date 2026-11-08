<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\User;
use App\Services\Pengajuan\PengajuanDashboardService;
use Illuminate\Http\Request;

class DashboardController extends BaseApiController
{
    public function __construct(protected PengajuanDashboardService $dashboard) {}

    public function userDashboard(Request $request)
    {
        $data = $this->dashboard->userStats($request->user());

        return $this->success($data, 'User dashboard');
    }

    public function adminDashboard(Request $request)
    {
        return $this->success($this->dashboard->adminStats($request->user()), 'Admin dashboard');
    }

    public function administrasiDashboard(Request $request)
    {
        return $this->success($this->dashboard->administrasiStats($request->user()), 'Administrasi dashboard');
    }
}
