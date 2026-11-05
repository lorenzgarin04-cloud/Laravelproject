<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'store_name'       => 'De Guzman Drugmart',
        'store_tagline'    => 'Dispensary & Clinical Supplies',
        'store_address'    => 'Urdaneta City, Pangasinan',
        'store_phone'      => '',
        'store_tin'        => '',
        'low_stock_below'  => '10',
        'expiry_warn_days' => '30',
        'email_digest'     => '1',
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = Cache::rememberForever('settings.all', fn () => static::pluck('value', 'key')->all());

        return $all[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('settings.all');
    }
}
