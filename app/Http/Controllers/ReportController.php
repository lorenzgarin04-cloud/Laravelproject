<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SaleItem;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public const TYPES = [
        'daily'      => 'Daily Sales & Stock',
        'detailed'   => 'Sales Report (Detailed)',
        'by_product' => 'Sales by Product (Summary)',
    ];

    public static function categories(): array
    {
        return ['all' => 'All Products'] + Product::TYPES;
    }

    public function index(Request $request)
    {
        [$type, $category, $date, $from, $to] = $this->filters($request);

        $common = [
            'types'      => self::TYPES,
            'categories' => self::categories(),
            'type'       => $type,
            'category'   => $category,
            'date'       => $date,
            'from'       => $from,
            'to'         => $to,
        ];

        if ($type === 'daily') {
            return view('reports.index', $common + ['daily' => $this->dailyData($date, $category)]);
        }

        return view('reports.index', $common + [
            'rows'    => $this->query($type, $from, $to, $category)->paginate(10)->withQueryString(),
            'revenue' => $this->netRevenue($from, $to, $category),
            'tiles'   => $this->typeTotals($from, $to),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$type, $category, $date, $from, $to] = $this->filters($request);

        if ($type === 'daily') {
            $daily = $this->dailyData($date, $category);
            $filename = 'dg-drugmart-daily-sales-stock-' . $date->toDateString() . '.csv';

            return response()->streamDownload(function () use ($daily) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, ['Type', 'Product', 'Unit Price', 'Expiration', 'End-of-day Stock', 'Sold Qty', 'Sold Amount (net)']);
                foreach ($daily['columns'] as $col) {
                    foreach ($col['rows'] as $r) {
                        fputcsv($out, [$col['label'], $r['name'], $r['price'], $r['expires']->format('Y-m-d'), $r['stock'], $r['qty'], $r['net']]);
                    }
                }
                fputcsv($out, []);
                fputcsv($out, ['TOTAL', '', '', '', '', '', $daily['grand']]);
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $rows = $this->query($type, $from, $to, $category)->get();
        $filename = sprintf('dg-drugmart-%s-%s_to_%s.csv', $type, $from->toDateString(), $to->toDateString());

        return response()->streamDownload(function () use ($rows, $type) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM para sa Excel
            fputcsv($out, $type === 'detailed'
                ? ['Date', 'Invoice', 'Product', 'Type', 'Quantity', 'Amount (before discount)', 'Senior/PWD Discount', 'Net Amount']
                : ['Product', 'Total Quantity', 'Amount (before discount)', 'Senior/PWD Discount', 'Net Amount']);

            foreach ($rows as $r) {
                fputcsv($out, $type === 'detailed'
                    ? [Carbon::parse($r->sold_at)->format('Y-m-d H:i'), $r->invoice_no, $r->product_name, $r->product_type, $r->quantity, $r->line_total, $r->discount, $r->net_total]
                    : [$r->product_name, $r->quantity, $r->line_total, $r->discount, $r->net_total]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        $v = $request->validate([
            'type'     => ['nullable', 'in:' . implode(',', array_keys(self::TYPES))],
            'category' => ['nullable', 'in:' . implode(',', array_keys(self::categories()))],
            'date'     => ['nullable', 'date'],
            'from'     => ['nullable', 'date'],
            'to'       => ['nullable', 'date'],
        ]);

        $type = $v['type'] ?? 'daily';
        $category = $v['category'] ?? 'all';

        $date = isset($v['date']) ? Carbon::parse($v['date'])->startOfDay() : today();
        if ($date->gt(today())) {
            $date = today();
        }

        $from = isset($v['from']) ? Carbon::parse($v['from'])->startOfDay() : now()->startOfMonth();
        $to   = isset($v['to'])   ? Carbon::parse($v['to'])->endOfDay()     : now()->endOfDay();
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$type, $category, $date, $from, $to];
    }

    /** Base query: lahat ng linya ng natapos na benta, kasama ang product type. */
    private function lines(Carbon $from, Carbon $to, string $category = 'all')
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$from, $to])
            ->when($category !== 'all', fn ($q) => $q->where('products.type', $category));
    }

    private function query(string $type, Carbon $from, Carbon $to, string $category)
    {
        $base = $this->lines($from, $to, $category);

        if ($type === 'by_product') {
            return $base
                ->select(
                    'sale_items.product_name',
                    DB::raw('SUM(sale_items.quantity) as quantity'),
                    DB::raw('SUM(sale_items.line_total) as line_total'),
                    DB::raw('SUM(sale_items.discount) as discount'),
                    DB::raw('SUM(sale_items.line_total - sale_items.discount) as net_total')
                )
                ->groupBy('sale_items.product_name')
                ->orderByDesc('net_total');
        }

        return $base
            ->select(
                'sale_items.product_name', 'sale_items.quantity', 'sale_items.line_total', 'sale_items.discount',
                DB::raw('(sale_items.line_total - sale_items.discount) as net_total'),
                'sales.id as sale_id', 'sales.invoice_no', 'sales.created_at as sold_at',
                'products.type as product_type', 'products.subtitle', 'products.unit'
            )
            ->orderByDesc('sales.created_at')->orderByDesc('sale_items.id');
    }

    private function netRevenue(Carbon $from, Carbon $to, string $category): float
    {
        return (float) $this->lines($from, $to, $category)->sum(DB::raw('sale_items.line_total - sale_items.discount'));
    }

    /** Net sales ng bawat type sa napiling petsa (para sa Branded / Generic / Grocery cards). */
    private function typeTotals(Carbon $from, Carbon $to): array
    {
        $sums = $this->lines($from, $to)
            ->groupBy('products.type')
            ->selectRaw('products.type as ptype, SUM(sale_items.line_total - sale_items.discount) as net')
            ->pluck('net', 'ptype');

        return collect(Product::TYPES)->map(fn ($label, $key) => (float) ($sums[$key] ?? 0))->all();
    }

    /** Daily Sales & Stock: bawat type ay may sariling column; stock = natira sa katapusan ng araw. */
    private function dailyData(Carbon $date, string $category): array
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();
        $lowBelow = (int) Setting::get('low_stock_below');
        $warnDays = (int) Setting::get('expiry_warn_days');

        $completed = fn () => SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed');

        $soldToday = $completed()
            ->whereBetween('sales.created_at', [$start, $end])
            ->groupBy('sale_items.product_id')
            ->selectRaw('sale_items.product_id as pid, SUM(sale_items.quantity) as qty, SUM(sale_items.line_total - sale_items.discount) as net')
            ->get()->keyBy('pid');

        // Ang mga nabenta PAGKATAPOS ng araw na ito: idinadagdag para malaman ang stock sa katapusan ng araw.
        $soldAfter = $completed()
            ->where('sales.created_at', '>', $end)
            ->groupBy('sale_items.product_id')
            ->selectRaw('sale_items.product_id as pid, SUM(sale_items.quantity) as qty')
            ->pluck('qty', 'pid');

        $products = Product::withTrashed()
            ->where(fn ($q) => $q->whereNull('deleted_at')->orWhereIn('id', $soldToday->keys()->all()))
            ->when($category !== 'all', fn ($q) => $q->where('type', $category))
            ->get();

        $columns = [];
        $grand = 0.0;

        foreach (Product::TYPES as $key => $label) {
            if ($category !== 'all' && $category !== $key) {
                continue;
            }

            $rows = $products->where('type', $key)->map(function (Product $p) use ($soldToday, $soldAfter, $start, $lowBelow, $warnDays) {
                $sold = $soldToday->get($p->id);
                $qty = $sold ? (int) $sold->qty : 0;
                $net = $sold ? round((float) $sold->net, 2) : 0.0;
                $stock = max(0, $p->stock + (int) ($soldAfter[$p->id] ?? 0));

                $expired = $p->expiration_date->lt($start);
                $daysLeft = (int) $start->diffInDays($p->expiration_date, false);
                $expiring = ! $expired && $daysLeft <= $warnDays;
                $low = $stock < $lowBelow;

                $state = $qty > 0 ? 'sold' : ($expired ? 'expired' : (($low || $expiring) ? 'alert' : 'normal'));

                return [
                    'id' => $p->id, 'name' => $p->name, 'unit' => $p->unit, 'price' => (float) $p->price,
                    'expires' => $p->expiration_date, 'stock' => $stock, 'qty' => $qty, 'net' => $net,
                    'state' => $state, 'low' => $low, 'expiring' => $expiring, 'expired' => $expired, 'days_left' => $daysLeft,
                    'group' => $qty > 0 ? 0 : ($expired ? 2 : 1),
                ];
            })
                ->sort(fn ($a, $b) => [$a['group'], -$a['stock'], $a['name']] <=> [$b['group'], -$b['stock'], $b['name']])
                ->values()->all();

            $total = round(array_sum(array_column($rows, 'net')), 2);
            $grand += $total;

            $columns[$key] = [
                'label' => $label,
                'rows'  => $rows,
                'items' => count($rows),
                'sold'  => count(array_filter($rows, fn ($r) => $r['qty'] > 0)),
                'total' => $total,
            ];
        }

        return ['date' => $date, 'columns' => $columns, 'grand' => round($grand, 2)];
    }
}
