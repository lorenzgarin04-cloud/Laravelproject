<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public const SENIOR_PWD_RATE = 0.20; // RA 9994 / RA 10754

    /** Record a Sale (POS) screen */
    public function create()
    {
        return view('sales.create', [
            'initial'  => $this->sellable()->orderBy('name')->limit(8)->get()->map(fn ($p) => $this->present($p)),
            'register' => 'Cash Register #1',
        ]);
    }

    /** Live search para sa POS */
    public function search(Request $request): JsonResponse
    {
        $items = $this->sellable()
            ->search(trim((string) $request->query('q', '')))
            ->orderBy('name')->limit(12)->get()
            ->map(fn ($p) => $this->present($p));

        return response()->json($items);
    }

    /** Complete Sale */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items'              => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1', 'max:10000'],
            'senior_pwd'         => ['boolean'],
            'cash_received'      => ['required', 'numeric', 'min:0', 'max:99999999'],
        ]);

        $sale = DB::transaction(function () use ($data, $request) {
            $products = Product::whereIn('id', collect($data['items'])->pluck('product_id'))
                ->lockForUpdate()->get()->keyBy('id');

            $subtotal = 0;
            $lines = [];

            foreach ($data['items'] as $row) {
                $product = $products[$row['product_id']];
                $qty = (int) $row['quantity'];

                if ($product->expiration_date->isPast()) {
                    throw ValidationException::withMessages(['items' => "{$product->name} is already expired and cannot be sold."]);
                }
                if ($product->stock < $qty) {
                    throw ValidationException::withMessages(['items' => "Not enough stock for {$product->name} (only {$product->stock} left)."]);
                }

                $line = round($product->price * $qty, 2);
                $subtotal += $line;
                $lines[] = [$product, $qty, $line];
            }

            $seniorPwd = (bool) ($data['senior_pwd'] ?? false);
            $discount = $seniorPwd ? round($subtotal * self::SENIOR_PWD_RATE, 2) : 0;
            $total = round($subtotal - $discount, 2);
            $cash = round((float) $data['cash_received'], 2);

            if ($cash < $total) {
                throw ValidationException::withMessages(['cash_received' => 'Cash received is less than the total due.']);
            }

            $sale = Sale::create([
                'invoice_no'    => Sale::nextInvoiceNo(),
                'user_id'       => $request->user()->id,
                'subtotal'      => $subtotal,
                'senior_pwd'    => $seniorPwd,
                'discount'      => $discount,
                'total'         => $total,
                'cash_received' => $cash,
                'change_due'    => round($cash - $total, 2),
                'status'        => 'completed',
            ]);

            foreach ($lines as [$product, $qty, $line]) {
                SaleItem::create([
                    'sale_id'      => $sale->id,
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'unit_price'   => $product->price,
                    'quantity'     => $qty,
                    'line_total'   => $line,
                ]);
                $product->decrement('stock', $qty);
            }

            return $sale;
        });

        return response()->json(['redirect' => route('sales.show', $sale)]);
    }

    /** Receipt Export screen */
    public function show(Sale $sale)
    {
        $sale->load('items', 'cashier');

        return view('sales.receipt', compact('sale'));
    }

    /** Sales history ("View All") */
    public function index()
    {
        return view('sales.index', [
            'sales' => Sale::with('items', 'cashier')->latest()->paginate(15),
        ]);
    }

    private function sellable()
    {
        return Product::query()->where('stock', '>', 0)->whereDate('expiration_date', '>=', today());
    }

    private function present(Product $p): array
    {
        return [
            'id'       => $p->id,
            'name'     => $p->name,
            'category' => $p->category_label,
            'subtitle' => $p->subtitle,
            'unit'     => $p->unit,
            'price'    => (float) $p->price,
            'stock'    => $p->stock,
            'low'      => $p->isLowStock(),
            'photo'    => $p->photo_url,
        ];
    }
}
