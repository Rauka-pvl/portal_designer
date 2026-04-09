<?php

namespace App\Http\Controllers\Supplier;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Http\Controllers\Controller;

class SupplierPortalController extends Controller
{
    public function index(Request $request): View
    {
        $supplier = Supplier::query()
            ->where('account_user_id', (int) $request->user()->id)
            ->first();

        return view('supplier.index', [
            'supplier' => $supplier,
        ]);
    }
}

