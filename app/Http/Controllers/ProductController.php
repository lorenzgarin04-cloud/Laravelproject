<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'all');
        $term = trim((string) $request->query('q', ''));

        $query = Product::query()->search($term);

        match ($filter) {
            'branded', 'generic', 'grocery' => $query->where('type', $filter),
            'low'      => $query->lowStock(),
            'expiring' => $query->expiringSoon(),
            'expired'  => $query->expired(),
            default    => null,
        };

        $counts = [
            'all'      => Product::count(),
            'branded'  => Product::where('type', 'branded')->count(),
            'generic'  => Product::where('type', 'generic')->count(),
            'grocery'  => Product::where('type', 'grocery')->count(),
            'low'      => Product::lowStock()->count(),
            'expiring' => Product::expiringSoon()->count(),
            'expired'  => Product::expired()->count(),
        ];

        return view('products.index', [
            'products' => $query->orderBy('name')->paginate(12)->withQueryString(),
            'filter'   => $filter,
            'term'     => $term,
            'counts'   => $counts,
        ]);
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['photo', 'requires_rx', 'no_expiry']);
        $data['category'] = $request->requiresRx() ? 'rx' : 'otc';
        $data['expiration_date'] = $request->noExpiry() ? null : $data['expiration_date'];
        $data['sku'] = Product::generateSku($data['name']);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('products', 'public');
        }

        $product = Product::create($data);

        if ($product->stock > 0) {
            StockMovement::record($product, (int) $product->stock, 'initial', $request->user()->id, null, 'New product');
        }

        return redirect()->route('products.show', $product)->with('success', 'Product saved to inventory.');
    }

    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->safe()->except(['photo', 'requires_rx', 'no_expiry']);
        $data['category'] = $request->requiresRx() ? 'rx' : 'otc';
        $data['expiration_date'] = $request->noExpiry() ? null : $data['expiration_date'];

        if ($request->hasFile('photo')) {
            if ($product->photo_path) {
                Storage::disk('public')->delete($product->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('products', 'public');
        }

        $stockBefore = (int) $product->stock;
        $product->update($data);

        if ((int) $product->stock !== $stockBefore) {
            StockMovement::record($product, (int) $product->stock - $stockBefore, 'adjust', $request->user()->id, null, 'Edited in product form');
        }

        return redirect()->route('products.show', $product)->with('success', 'Changes saved.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Only an administrator can delete products.');

        $product->delete(); // soft delete: nananatili ang history ng benta

        return redirect()->route('products.index')->with('success', 'Product removed from catalog.');
    }

    /** +/- stock buttons sa Products grid (AJAX). */
    public function adjustStock(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate(['delta' => ['required', 'integer', 'in:-1,1']]);

        $before = (int) $product->stock;

        // Atomic: ang database mismo ang nagbibilang (hindi nawawala ang pindot kahit sabay ang dalawang admin).
        if ($data['delta'] > 0) {
            Product::whereKey($product->id)->increment('stock');
        } else {
            Product::whereKey($product->id)->where('stock', '>', 0)->decrement('stock');
        }

        $product->refresh();

        if ((int) $product->stock !== $before) {
            StockMovement::record($product, (int) $product->stock - $before, 'adjust', $request->user()->id, null, 'Manual +/- on Products');
        }

        return response()->json([
            'stock'  => $product->stock,
            'status' => $product->status,
        ]);
    }
}
