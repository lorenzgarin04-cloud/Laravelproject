<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Daily Report: bawat produkto kada araw: Last stock, Sold, Added/Removed, Stock left.
 * Ang stock sa katapusan ng isang araw = kasalukuyang stock minus ang lahat ng galaw PAGKATAPOS ng araw na iyon.
 */
class DailyReport
{
    public static function build(Carbon $date): array
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();
        $lowBelow = (int) Setting::get('low_stock_below');
        $warnDays = (int) Setting::get('expiry_warn_days');

        // Benta sa araw na ito (net ng Senior/PWD discount)
        $sales = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$start, $end])
            ->groupBy('sale_items.product_id')
            ->selectRaw('sale_items.product_id as pid, SUM(sale_items.quantity) as qty, SUM(sale_items.line_total - sale_items.discount) as net')
            ->get()->keyBy('pid');

        // Lahat ng galaw ng stock pagkatapos ng araw na ito
        $after = DB::table('stock_movements')
            ->where('created_at', '>', $end)
            ->groupBy('product_id')
            ->selectRaw('product_id as pid, SUM(quantity) as net')
            ->pluck('net', 'pid');

        // Mga galaw sa mismong araw na ito
        $onDay = DB::table('stock_movements')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('product_id')
            ->selectRaw("product_id as pid, SUM(quantity) as net, SUM(CASE WHEN type = 'sale' THEN quantity ELSE 0 END) as sale_net")
            ->get()->keyBy('pid');

        $ids = $sales->keys()->merge($onDay->keys())->unique()->values()->all();

        $products = Product::withTrashed()
            ->where(fn ($q) => $q->where('created_at', '<=', $end)->orWhereIn('id', $ids))
            ->where(fn ($q) => $q->whereNull('deleted_at')->orWhereIn('id', $ids))
            ->get();

        $columns = [];
        $grand = 0.0;
        $itemsSold = 0;

        foreach (Product::TYPES as $key => $label) {
            $rows = $products->where('type', $key)->map(function (Product $p) use ($sales, $onDay, $after, $start, $lowBelow, $warnDays) {
                $s = $sales->get($p->id);
                $m = $onDay->get($p->id);

                $soldQty = $s ? (int) $s->qty : 0;
                $soldNet = $s ? round((float) $s->net, 2) : 0.0;
                $onNet = $m ? (int) $m->net : 0;
                $saleNet = $m ? (int) $m->sale_net : 0;

                $stockLeft = max(0, (int) $p->stock - (int) ($after[$p->id] ?? 0));
                $lastStock = max(0, $stockLeft - $onNet);
                $added = $onNet - $saleNet; // dagdag/bawas na hindi benta

                $noExpiry = $p->expiration_date === null;
                $expired = ! $noExpiry && $p->expiration_date->lt($start);
                $daysLeft = $noExpiry ? null : (int) $start->diffInDays($p->expiration_date, false);
                $expiring = ! $noExpiry && ! $expired && $daysLeft <= $warnDays;
                $low = $stockLeft < $lowBelow;

                $state = $expired ? 'expired' : ($soldQty > 0 ? 'sold' : (($low || $expiring) ? 'alert' : 'normal'));

                return [
                    'id' => $p->id, 'name' => $p->name, 'unit' => $p->unit, 'price' => (float) $p->price, 'rx' => $p->isRx(),
                    'expires' => $p->expiration_date, 'days_left' => $daysLeft,
                    'last' => $lastStock, 'qty' => $soldQty, 'net' => $soldNet, 'added' => $added, 'stock' => $stockLeft,
                    'low' => $low, 'expiring' => $expiring, 'expired' => $expired, 'state' => $state,
                    'group' => $state === 'sold' ? 0 : ($state === 'expired' ? 2 : 1),
                ];
            })
                ->sort(fn ($a, $b) => [$a['group'], -$a['stock'], $a['name']] <=> [$b['group'], -$b['stock'], $b['name']])
                ->values()->all();

            $total = round(array_sum(array_column($rows, 'net')), 2);
            $qty = (int) array_sum(array_column($rows, 'qty'));
            $grand += $total;
            $itemsSold += $qty;

            $columns[$key] = [
                'label' => $label,
                'rows'  => $rows,
                'items' => count($rows),
                'sold'  => count(array_filter($rows, fn ($r) => $r['qty'] > 0)),
                'qty'   => $qty,
                'total' => $total,
            ];
        }

        $salesCount = DB::table('sales')->where('status', 'completed')->whereBetween('created_at', [$start, $end])->count();

        return [
            'date'       => $date->copy()->startOfDay(),
            'columns'    => $columns,
            'grand'      => round($grand, 2),
            'items_sold' => $itemsSold,
            'sales'      => $salesCount,
        ];
    }

    /** Mabilis na kabuuan ng isang araw (para sa Dashboard): bawat type + kabuuan. */
    public static function dayTotals(Carbon $date): array
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $rows = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$start, $end])
            ->groupBy('products.type')
            ->selectRaw('products.type as ptype, SUM(sale_items.quantity) as qty, SUM(sale_items.line_total - sale_items.discount) as net')
            ->get()->keyBy('ptype');

        $types = [];
        $total = 0.0;
        foreach (Product::TYPES as $key => $label) {
            $r = $rows->get($key);
            $net = $r ? round((float) $r->net, 2) : 0.0;
            $types[$key] = ['label' => $label, 'qty' => $r ? (int) $r->qty : 0, 'net' => $net];
            $total += $net;
        }

        return [
            'types' => $types,
            'total' => round($total, 2),
            'sales' => DB::table('sales')->where('status', 'completed')->whereBetween('created_at', [$start, $end])->count(),
        ];
    }
}
