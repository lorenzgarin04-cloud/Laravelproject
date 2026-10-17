<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;

class DashboardController extends Controller
{
    public function index()
    {
        $todayTotal = (float) Sale::completed()->whereDate('created_at', today())->sum('total');
        $yesterdayTotal = (float) Sale::completed()->whereDate('created_at', today()->subDay())->sum('total');

        $delta = $yesterdayTotal > 0
            ? round((($todayTotal - $yesterdayTotal) / $yesterdayTotal) * 100, 1)
            : null;

        $lowStockProducts = Product::lowStock()->orderBy('stock')->limit(3)->get();
        $expiringProducts = Product::expiringSoon()->orderBy('expiration_date')->limit(2)->get();

        return view('dashboard.index', [
            'totalProducts'    => Product::count(),
            'lowStockCount'    => Product::lowStock()->count(),
            'expiringCount'    => Product::expiringSoon()->count(),
            'todayTotal'       => $todayTotal,
            'delta'            => $delta,
            'recentSales'      => Sale::with('items')->latest()->limit(4)->get(),
            'todayCount'       => Sale::completed()->whereDate('created_at', today())->count(),
            'lowStockProducts' => $lowStockProducts,
            'expiringProducts' => $expiringProducts,
            'criticalCount'    => $lowStockProducts->count() + $expiringProducts->count(),
        ]);
    }
}
