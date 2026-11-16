<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ImportSqlite extends Command
{
    protected $signature = 'db:import-sqlite {path=database/database.sqlite : Lokasyon ng lumang SQLite file}';

    protected $description = 'Kopyahin ang accounts, products, sales at settings mula sa lumang SQLite papunta sa kasalukuyang database (MySQL/phpMyAdmin)';

    /** Sunod-sunod para tama ang foreign keys. */
    private const TABLES = ['users', 'products', 'sales', 'sale_items', 'settings'];

    private const BRANDED_SKUS = ['VTC-120', 'CGH-060', 'EYE-015', 'ANT-250', 'BND-100'];

    public function handle(): int
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->error('Naka-SQLite pa ang .env mo. Palitan muna ang DB_CONNECTION=mysql at ang DB_DATABASE, tapos patakbuhin ang `php artisan migrate` bago ito.');
            return self::FAILURE;
        }

        $path = $this->argument('path');
        $full = is_file($path) ? realpath($path) : base_path($path);
        if (! is_file($full)) {
            $this->error("Hindi makita ang SQLite file: {$full}");
            return self::FAILURE;
        }

        config(['database.connections.old_sqlite' => [
            'driver' => 'sqlite', 'database' => $full, 'prefix' => '', 'foreign_key_constraints' => false,
        ]]);
        $old = DB::connection('old_sqlite');
        $oldSchema = Schema::connection('old_sqlite');

        try {
            // Alamin kung gaano ka-luma ang SQLite file para maitama ang data habang kinokopya.
            $ran = $oldSchema->hasTable('migrations') ? $old->table('migrations')->pluck('migration')->all() : [];
            $needsTimeShift = ! in_array('2026_11_06_000001_shift_old_sales_from_utc_to_manila', $ran, true);
            $lacksType = ! $oldSchema->hasColumn('products', 'type');
            $lacksDiscount = ! $oldSchema->hasColumn('sale_items', 'discount');

            Schema::disableForeignKeyConstraints();

            foreach (self::TABLES as $table) {
                if (! $oldSchema->hasTable($table)) {
                    $this->warn("Walang table na '{$table}' sa lumang database: nilaktawan.");
                    continue;
                }

                $targetColumns = array_flip(Schema::getColumnListing($table));
                $copied = 0;

                foreach ($old->table($table)->get()->chunk(200) as $chunk) {
                    $rows = $chunk->map(function ($row) use ($targetColumns, $table, $needsTimeShift) {
                        $row = array_intersect_key((array) $row, $targetColumns);

                        // Ang lumang oras ay UTC: gawing oras ng Pilipinas (+8 oras)
                        if ($needsTimeShift && in_array($table, ['sales', 'sale_items'], true)) {
                            foreach (['created_at', 'updated_at'] as $col) {
                                if (! empty($row[$col])) {
                                    $row[$col] = Carbon::parse($row[$col])->addHours(8)->format('Y-m-d H:i:s');
                                }
                            }
                        }

                        return $row;
                    })->all();

                    $copied += DB::table($table)->insertOrIgnore($rows);
                }

                $this->line(sprintf('%-12s nakopya: %d  (kabuuan ngayon: %d)', $table, $copied, DB::table($table)->count()));

                if ($table === 'products' && $lacksType) {
                    DB::table('products')->whereIn('sku', self::BRANDED_SKUS)->update(['type' => 'branded']);
                    $this->line('             (nilagyan ng Product Type ang mga sample na gamot)');
                }
            }

            if ($lacksDiscount) {
                $this->backfillDiscounts();
                $this->line('             (hinati ang Senior/PWD discount sa bawat linya)');
            }

            if ($needsTimeShift) {
                $this->line('             (naitama ang oras ng mga benta sa oras ng Pilipinas)');
            }
        } catch (Throwable $e) {
            $this->error('Pumalya: ' . $e->getMessage());
            return self::FAILURE;
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->info('Tapos na! Buksan ang phpMyAdmin at tingnan ang mga table. Ang mga larawan (storage/app/public) ay hindi gagalawin.');
        return self::SUCCESS;
    }

    /** Hatiin ang discount ng bawat Senior/PWD na benta sa mga linya nito (ang huling linya ang sumasalo ng sentimo). */
    private function backfillDiscounts(): void
    {
        DB::table('sales')->where('discount', '>', 0)->orderBy('id')->each(function ($sale) {
            $items = DB::table('sale_items')->where('sale_id', $sale->id)->orderBy('id')->get();
            if ($items->isEmpty() || (float) $sale->subtotal <= 0) {
                return;
            }

            $allocated = 0.0;
            $last = $items->count() - 1;

            foreach ($items as $i => $item) {
                $d = $i === $last
                    ? round((float) $sale->discount - $allocated, 2)
                    : round((float) $item->line_total / (float) $sale->subtotal * (float) $sale->discount, 2);
                $allocated += $d;

                DB::table('sale_items')->where('id', $item->id)->update(['discount' => $d]);
            }
        });
    }
}
