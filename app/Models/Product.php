<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku', 'name', 'category', 'subtitle', 'unit', 'price',
        'stock', 'expiration_date', 'photo_path', 'description',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'expiration_date' => 'date',
        ];
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /* ---------- Accessors / helpers ---------- */

    public function getPhotoUrlAttribute(): string
    {
        return $this->photo_path
            ? Storage::disk('public')->url($this->photo_path)
            : asset('images/no-image.svg');
    }

    public function isLowStock(): bool
    {
        return $this->stock < (int) Setting::get('low_stock_below');
    }

    public function isExpiringSoon(): bool
    {
        return $this->expiration_date->lte(now()->addDays((int) Setting::get('expiry_warn_days')));
    }

    /** out_of_stock | expiring | low_stock | in_stock */
    public function getStatusAttribute(): string
    {
        if ($this->stock === 0)      return 'out_of_stock';
        if ($this->isExpiringSoon()) return 'expiring';
        if ($this->isLowStock())     return 'low_stock';
        return 'in_stock';
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'rx'    => 'Rx',
            'both'  => 'Prescription & OTC',
            default => 'OTC',
        };
    }

    /* ---------- Scopes ---------- */

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        return $term
            ? $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%")
                ->orWhere('subtitle', 'like', "%{$term}%"))
            : $q;
    }

    public function scopeLowStock(Builder $q): Builder
    {
        return $q->where('stock', '<', (int) Setting::get('low_stock_below'));
    }

    public function scopeExpiringSoon(Builder $q): Builder
    {
        return $q->whereDate('expiration_date', '<=', now()->addDays((int) Setting::get('expiry_warn_days')));
    }

    public static function generateSku(string $name): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 3)) ?: 'MED';
        do {
            $sku = $prefix . '-' . random_int(10000, 99999);
        } while (static::withTrashed()->where('sku', $sku)->exists());

        return $sku;
    }
}
