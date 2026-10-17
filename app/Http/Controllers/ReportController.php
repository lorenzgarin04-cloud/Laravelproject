<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public const TYPES = [
        'detailed'   => 'Sales Report (Detailed)',
        'by_product' => 'Sales by Product (Summary)',
    ];

    public function index(Request $request)
    {
        [$type, $from, $to] = $this->filters($request);

        $rows = $this->query($type, $from, $to)->paginate(10)->withQueryString();

        return view('reports.index', [
            'types'   => self::TYPES,
            'type'    => $type,
            'from'    => $from,
            'to'      => $to,
            'rows'    => $rows,
            'revenue' => $this->revenue($from, $to),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$type, $from, $to] = $this->filters($request);
        $rows = $this->query($type, $from, $to)->get();

        $filename = sprintf('dg-drugmart-%s-%s_to_%s.csv', $type, $from->toDateString(), $to->toDateString());

        return response()->streamDownload(function () use ($rows, $type) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM para sa Excel
            fputcsv($out, $type === 'detailed'
                ? ['Date', 'Invoice', 'Product', 'Quantity', 'Amount']
                : ['Product', 'Total Quantity', 'Total Amount']);

            foreach ($rows as $r) {
                fputcsv($out, $type === 'detailed'
                    ? [Carbon::parse($r->sold_at)->format('Y-m-d H:i'), $r->invoice_no, $r->product_name, $r->quantity, $r->line_total]
                    : [$r->product_name, $r->quantity, $r->line_total]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'type' => ['nullable', 'in:' . implode(',', array_keys(self::TYPES))],
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date'],
        ]);

        $type = $validated['type'] ?? 'detailed';
        $from = isset($validated['from']) ? Carbon::parse($validated['from'])->startOfDay() : now()->startOfMonth();
        $to   = isset($validated['to'])   ? Carbon::parse($validated['to'])->endOfDay()     : now()->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$type, $from, $to];
    }

    private function query(string $type, Carbon $from, Carbon $to)
    {
        $base = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$from, $to]);

        if ($type === 'by_product') {
            return $base
                ->select('sale_items.product_name', DB::raw('SUM(sale_items.quantity) as quantity'), DB::raw('SUM(sale_items.line_total) as line_total'))
                ->groupBy('sale_items.product_name')
                ->orderByDesc('line_total');
        }

        return $base
            ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
            ->select(
                'sale_items.product_name', 'sale_items.quantity', 'sale_items.line_total',
                'sales.invoice_no', 'sales.created_at as sold_at',
                'products.subtitle', 'products.unit'
            )
            ->orderByDesc('sales.created_at')->orderByDesc('sale_items.id');
    }

    private function revenue(Carbon $from, Carbon $to): float
    {
        return (float) Sale::completed()->whereBetween('created_at', [$from, $to])->sum('total');
    }
}
