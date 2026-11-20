<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPES = ['branded' => 'Branded', 'generic' => 'Generic', 'grocery' => 'Grocery'];

    protected $fillable = [
        'sku', 'name', 'type', 'category', 'subtitle', 'unit', 'price',
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
            ? route('media', ['path' => $this->photo_path], false)
            : asset('images/no-image.svg');
    }

    public function isLowStock(): bool
    {
        return $this->stock < (int) Setting::get('low_stock_below');
    }

    public function isExpiringSoon(): bool
    {
        return $this->expiration_date !== null
            && ! $this->isExpired()
            && $this->expiration_date->lte(now()->addDays((int) Setting::get('expiry_warn_days')));
    }

    public function isExpired(): bool
    {
        return $this->expiration_date !== null && $this->expiration_date->lt(today());
    }

    /** expired | out_of_stock | expiring | low_stock | in_stock */
    public function getStatusAttribute(): string
    {
        if ($this->isExpired())      return 'expired';
        if ($this->stock === 0)      return 'out_of_stock';
        if ($this->isExpiringSoon()) return 'expiring';
        if ($this->isLowStock())     return 'low_stock';
        return 'in_stock';
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }

    public function isRx(): bool
    {
        return $this->category === 'rx';
    }

    /** Hal: "Generic • Rx • Antibiotic" (para sa POS at listahan) */
    public function getTagLineAttribute(): string
    {
        return collect([$this->type_label, $this->isRx() ? 'Rx' : null, $this->subtitle])->filter()->implode(' • ');
    }

    /* ---------- Scopes ---------- */

    public function scopeOfType(Builder $q, ?string $type): Builder
    {
        return $type && isset(self::TYPES[$type]) ? $q->where('type', $type) : $q;
    }

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

    /**
     * Mabebenta lang kung HIGIT pa sa warning days (default 30) bago mag-expire.
     * Pag dumating na ang 30 araw (o mas malapit pa), hindi na pwedeng ibenta.
     */
    public function isSellable(): bool
    {
        if ($this->expiration_date === null) {
            return true; // walang expiration (hal. grocery)
        }

        return $this->expiration_date->gt(today()->addDays((int) Setting::get('expiry_warn_days')));
    }

    public function scopeSellable(Builder $q): Builder
    {
        $limit = today()->addDays((int) Setting::get('expiry_warn_days'))->toDateString();

        return $q->where('stock', '>', 0)
            ->where(fn ($w) => $w->whereNull('expiration_date')->orWhereDate('expiration_date', '>', $limit));
    }

    /** Malapit nang mag-expire: hindi pa expired, pero pasok sa warning days. */
    public function scopeExpiringSoon(Builder $q): Builder
    {
        return $q->whereDate('expiration_date', '>=', today())
            ->whereDate('expiration_date', '<=', now()->addDays((int) Setting::get('expiry_warn_days')));
    }

    /** Expired na (lampas na ang petsa). */
    public function scopeExpired(Builder $q): Builder
    {
        return $q->whereDate('expiration_date', '<', today());
    }

    /** Expired o malapit nang mag-expire (para sa Urgent Alerts). */
    public function scopeExpiringOrExpired(Builder $q): Builder
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
