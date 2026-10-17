<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
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
            'rx'       => $query->whereIn('category', ['rx', 'both']),
            'otc'      => $query->whereIn('category', ['otc', 'both']),
            'low'      => $query->lowStock(),
            'expiring' => $query->expiringSoon(),
            default    => null,
        };

        $counts = [
            'all'      => Product::count(),
            'rx'       => Product::whereIn('category', ['rx', 'both'])->count(),
            'otc'      => Product::whereIn('category', ['otc', 'both'])->count(),
            'low'      => Product::lowStock()->count(),
            'expiring' => Product::expiringSoon()->count(),
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
        $data = $request->safe()->except('photo');
        $data['sku'] = Product::generateSku($data['name']);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('products', 'public');
        }

        $product = Product::create($data);

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
        $data = $request->safe()->except('photo');

        if ($request->hasFile('photo')) {
            if ($product->photo_path) {
                Storage::disk('public')->delete($product->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('products', 'public');
        }

        $product->update($data);

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

        $newStock = max(0, $product->stock + $data['delta']);
        $product->update(['stock' => $newStock]);

        return response()->json([
            'stock'  => $product->stock,
            'status' => $product->status,
        ]);
    }
}
