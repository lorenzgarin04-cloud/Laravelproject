<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = [
        'invoice_no', 'user_id', 'subtotal', 'senior_pwd', 'discount',
        'total', 'cash_received', 'change_due', 'status',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'cash_received' => 'decimal:2',
            'change_due' => 'decimal:2',
            'senior_pwd' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeCompleted(Builder $q): Builder
    {
        return $q->where('status', 'completed');
    }

    public static function nextInvoiceNo(): string
    {
        $count = static::whereYear('created_at', now()->year)->count() + 1;

        return sprintf('INV-%d-%04d', now()->year, $count);
    }
}
