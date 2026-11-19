<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
            'items'                 => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id'    => ['required', 'integer', 'distinct', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'items.*.quantity'      => ['required', 'integer', 'min:1', 'max:10000'],
            'senior_pwd'            => ['boolean'],
            'prescription_verified' => ['boolean'],
            'cash_received'         => ['required', 'numeric', 'min:0', 'max:99999999'],
        ], [
            'items.*.product_id.exists' => 'One of the items in your cart is no longer available. Please remove it and try again.',
        ]);

        $sale = DB::transaction(function () use ($data, $request) {
            $products = Product::whereIn('id', collect($data['items'])->pluck('product_id'))
                ->lockForUpdate()->get()->keyBy('id');

            $subtotal = 0;
            $lines = [];
            $hasRx = false;

            foreach ($data['items'] as $row) {
                $product = $products->get($row['product_id']);
                if (! $product) {
                    throw ValidationException::withMessages(['items' => 'One of the items in your cart is no longer available. Please remove it and try again.']);
                }
                $qty = (int) $row['quantity'];
                $hasRx = $hasRx || $product->category === 'rx';

                if (! $product->isSellable()) {
                    $days = (int) Setting::get('expiry_warn_days');
                    $message = $product->isExpired()
                        ? "{$product->name} is already expired and cannot be sold."
                        : "{$product->name} expires on {$product->expiration_date->format('M d, Y')} (within {$days} days) and can no longer be sold. Please remove it from the cart.";

                    throw ValidationException::withMessages(['items' => $message]);
                }
                if ($product->stock < $qty) {
                    throw ValidationException::withMessages(['items' => "Not enough stock for {$product->name} (only {$product->stock} left)."]);
                }

                $line = round($product->price * $qty, 2);
                $subtotal += $line;
                $lines[] = [$product, $qty, $line];
            }

            $rxVerified = (bool) ($data['prescription_verified'] ?? false);
            if ($hasRx && ! $rxVerified) {
                throw ValidationException::withMessages(['prescription_verified' => 'Please confirm that the prescription was verified before selling Rx items.']);
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
                'prescription_verified' => $hasRx && $rxVerified,
                'discount'      => $discount,
                'total'         => $total,
                'cash_received' => $cash,
                'change_due'    => round($cash - $total, 2),
                'status'        => 'completed',
            ]);

            // Hatiin ang Senior/PWD discount sa bawat linya (ang huling linya ang sumasalo ng sentimo),
            // para ang total ng Net sa Reports ay eksaktong tugma sa Revenue.
            $allocated = 0.0;
            $lastIndex = count($lines) - 1;

            foreach ($lines as $i => [$product, $qty, $line]) {
                if ($discount <= 0 || $subtotal <= 0) {
                    $lineDiscount = 0.0;
                } elseif ($i === $lastIndex) {
                    $lineDiscount = round($discount - $allocated, 2);
                } else {
                    $lineDiscount = round($line / $subtotal * $discount, 2);
                }
                $allocated += $lineDiscount;

                SaleItem::create([
                    'sale_id'      => $sale->id,
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'unit_price'   => $product->price,
                    'quantity'     => $qty,
                    'line_total'   => $line,
                    'discount'     => $lineDiscount,
                ]);
                $product->decrement('stock', $qty);
                StockMovement::record($product, -$qty, 'sale', $request->user()->id, $sale->id);
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

    /** Ang lumalabas lang sa POS: may stock at HIGIT pa sa warning days (30) bago mag-expire. */
    private function sellable()
    {
        return Product::query()->sellable();
    }

    private function present(Product $p): array
    {
        return [
            'id'       => $p->id,
            'name'     => $p->name,
            'tag'      => $p->tag_line,
            'type'     => $p->type,
            'rx'       => $p->isRx(),
            'unit'     => $p->unit,
            'price'    => (float) $p->price,
            'stock'    => $p->stock,
            'low'      => $p->isLowStock(),
            'photo'    => $p->photo_url,
        ];
    }
}
