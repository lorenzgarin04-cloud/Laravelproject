<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = ['product_id', 'user_id', 'sale_id', 'type', 'quantity', 'stock_after', 'note'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /** Itala ang pagbabago ng stock. Tawagin PAGKATAPOS mabago ang stock ng produkto. */
    public static function record(Product $product, int $change, string $type, ?int $userId = null, ?int $saleId = null, ?string $note = null): self
    {
        return static::create([
            'product_id'  => $product->id,
            'user_id'     => $userId,
            'sale_id'     => $saleId,
            'type'        => $type,
            'quantity'    => $change,
            'stock_after' => max(0, (int) $product->stock),
            'note'        => $note,
        ]);
    }
}
