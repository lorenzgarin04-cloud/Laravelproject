<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public const PERIODS = ['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year'];

    public function index(Request $request)
    {
        $period = (string) $request->query('period', 'week');
        if (! isset(self::PERIODS[$period])) {
            $period = 'week';
        }

        [$from, $to] = $this->range($period);
        $days = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;

        $prevTo = $from->copy()->subSecond();
        $prevFrom = $from->copy()->subDays($days)->startOfDay();

        $rows = $this->rows($from, $to);
        $prevRows = $this->rows($prevFrom, $prevTo);

        $typeKeys = array_keys(Product::TYPES);
        $buckets = $this->buckets($period, $from, $to);

        // ---- Sales over time (per bucket, per type) ----
        $series = [];
        foreach ($buckets as $key => $label) {
            $series[$key] = ['key' => $key, 'label' => $label] + array_fill_keys($typeKeys, 0.0);
        }
        foreach ($rows as $row) {
            $key = $this->bucketKey($period, Carbon::parse($row->sold_at));
            if (isset($series[$key]) && isset($series[$key][$row->type])) {
                $series[$key][$row->type] += (float) $row->net;
            }
        }
        $series = collect($series)->map(function ($b) use ($typeKeys) {
            foreach ($typeKeys as $t) {
                $b[$t] = round($b[$t], 2);
            }
            $b['total'] = round(array_sum(array_intersect_key($b, array_flip($typeKeys))), 2);
            return $b;
        })->values()->all();

        $max = $series ? max(array_column($series, 'total')) : 0;
        [$ticks, $top] = $this->ticks((float) $max);

        // ---- Totals + comparison ----
        $now = $this->summarize($rows, $typeKeys);
        $before = $this->summarize($prevRows, $typeKeys);

        $share = [];
        foreach (Product::TYPES as $key => $label) {
            $share[$key] = [
                'label'   => $label,
                'amount'  => $now['net'][$key],
                'percent' => $now['net']['all'] > 0 ? round($now['net'][$key] / $now['net']['all'] * 100, 1) : 0.0,
            ];
        }

        // ---- Top 5 best sellers ----
        $top5 = $rows->groupBy('product_id')->map(fn ($g) => [
            'name' => $g->first()->product_name,
            'type' => $g->first()->type,
            'qty'  => (int) $g->sum('quantity'),
            'net'  => round((float) $g->sum('net'), 2),
        ])->sortByDesc('net')->take(5)->values()->all();

        $deltas = ['all' => $this->delta($now['net']['all'], $before['net']['all'])];
        foreach ($typeKeys as $t) {
            $deltas[$t] = $this->delta($now['net'][$t], $before['net'][$t]);
        }

        return view('reports.analytics', [
            'periods'     => self::PERIODS,
            'period'      => $period,
            'from'        => $from,
            'to'          => $to,
            'rangeLabel'  => $from->isSameDay($to) ? $from->format('M j, Y') : $from->format('M j') . ' – ' . $to->format('M j, Y'),
            'compareText' => $period === 'today' ? 'yesterday' : "previous {$days} days",
            'now'         => $now,
            'deltas'      => $deltas,
            'share'       => $share,
            'top5'        => $top5,
            'chart'       => ['series' => $series, 'ticks' => $ticks, 'top' => $top, 'types' => Product::TYPES],
            'hasSales'    => $now['orders'] > 0,
        ]);
    }

    private function range(string $period): array
    {
        $end = now()->endOfDay();

        return match ($period) {
            'today' => [now()->startOfDay(), $end],
            'month' => [now()->startOfMonth(), $end],
            'year'  => [now()->startOfYear(), $end],
            default => [now()->subDays(6)->startOfDay(), $end],
        };
    }

    private function rows(Carbon $from, Carbon $to)
    {
        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$from, $to])
            ->select(
                'sales.id as sale_id', 'sales.created_at as sold_at',
                'sale_items.product_id', 'sale_items.product_name', 'sale_items.quantity',
                'products.type', DB::raw('(sale_items.line_total - sale_items.discount) as net')
            )
            ->get();
    }

    /** key => label ng bawat bar sa chart */
    private function buckets(string $period, Carbon $from, Carbon $to): array
    {
        $out = [];

        if ($period === 'today') {
            for ($h = 0; $h < 24; $h++) {
                $out[sprintf('%02d', $h)] = Carbon::createFromTime($h)->format('ga');
            }
        } elseif ($period === 'year') {
            for ($m = $from->copy()->startOfMonth(); $m->lte($to); $m->addMonth()) {
                $out[$m->format('Y-m')] = $m->format('M');
            }
        } else {
            for ($d = $from->copy()->startOfDay(); $d->lte($to); $d->addDay()) {
                $out[$d->format('Y-m-d')] = $d->format('D j');
            }
        }

        return $out;
    }

    private function bucketKey(string $period, Carbon $at): string
    {
        return match ($period) {
            'today' => $at->format('H'),
            'year'  => $at->format('Y-m'),
            default => $at->format('Y-m-d'),
        };
    }

    private function summarize($rows, array $typeKeys): array
    {
        $net = array_fill_keys($typeKeys, 0.0) + ['all' => 0.0];
        $qty = array_fill_keys($typeKeys, 0);

        foreach ($rows as $r) {
            if (! isset($net[$r->type])) {
                continue;
            }
            $net[$r->type] += (float) $r->net;
            $net['all'] += (float) $r->net;
            $qty[$r->type] += (int) $r->quantity;
        }

        return [
            'net'    => array_map(fn ($v) => round($v, 2), $net),
            'qty'    => $qty,
            'orders' => $rows->pluck('sale_id')->unique()->count(),
        ];
    }

    private function delta(float $current, float $previous): array
    {
        if ($previous <= 0) {
            return ['kind' => $current > 0 ? 'new' : 'none', 'pct' => 0.0, 'up' => $current > 0];
        }

        $pct = round(($current - $previous) / $previous * 100, 1);

        return ['kind' => 'pct', 'pct' => $pct, 'up' => $pct >= 0];
    }

    /** Magandang mga numero para sa Y axis. */
    private function ticks(float $max): array
    {
        if ($max <= 0) {
            return [[0, 25, 50, 75, 100], 100.0];
        }

        $raw = $max / 4;
        $mag = 10 ** floor(log10($raw));
        $norm = $raw / $mag;
        $step = ($norm <= 1 ? 1 : ($norm <= 2 ? 2 : ($norm <= 2.5 ? 2.5 : ($norm <= 5 ? 5 : 10)))) * $mag;
        $top = ceil($max / $step) * $step;

        $ticks = [];
        for ($v = 0; $v <= $top + 0.0001; $v += $step) {
            $ticks[] = round($v, 2);
        }

        return [$ticks, (float) $top];
    }
}
