<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SaleItem;
use App\Services\DailyReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /** Mga ulat na mapipili sa "Detailed" page (ang Daily Report ay sariling page). */
    public const TYPES = [
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
            $full = DailyReport::build($date);

            // Ang mga card sa itaas ay laging kumpleto; ang mga talahanayan lang ang sinasala ng category.
            $daily = $full;
            if ($category !== 'all') {
                $daily['columns'] = array_intersect_key($full['columns'], [$category => true]);
            }

            return view('reports.daily', ['daily' => $daily, 'totals' => $full, 'date' => $date, 'category' => $category]);
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
            $daily = DailyReport::build($date);
            $filename = 'dg-drugmart-daily-report-' . $date->toDateString() . '.csv';

            return response()->streamDownload(function () use ($daily, $category) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, ['Type', 'Product', 'Unit Price', 'Expiration', 'Last Stock', 'Sold Qty', 'Sold Amount (net)', 'Added/Removed', 'Stock Left']);
                foreach ($daily['columns'] as $key => $col) {
                    if ($category !== 'all' && $category !== $key) {
                        continue;
                    }
                    foreach ($col['rows'] as $r) {
                        fputcsv($out, [$col['label'], $r['name'], $r['price'], ($r['expires'] ? $r['expires']->format('Y-m-d') : 'No expiry'), $r['last'], $r['qty'], $r['net'], $r['added'], $r['stock']]);
                    }
                }
                fputcsv($out, []);
                fputcsv($out, ['TOTAL SOLD', '', '', '', '', $daily['items_sold'], $daily['grand']]);
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
}
