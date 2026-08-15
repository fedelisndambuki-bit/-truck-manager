<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Transaction;
use App\Modules\Settlement\Settlement;
use Illuminate\Http\Request;

class ReportingController extends Controller
{
    /**
     * GET /api/transactions
     */
    public function transactions(Request $request)
    {
        $companyId = $request->query('company_id');
        
        $query = Transaction::query();
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return response()->json($query->latest()->paginate(20));
    }

    /**
     * GET /api/settlements
     */
    public function settlements(Request $request)
    {
        $companyId = $request->query('company_id');
        
        $query = Settlement::query();
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return response()->json($query->latest()->paginate(20));
    }
}
