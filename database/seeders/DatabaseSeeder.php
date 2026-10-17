<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Admin account (palitan agad ang password pagkatapos mag-login!) ----
        $admin = User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@deguzmandrugmart.ph')],
            [
                'name'     => 'Administrator',
                'password' => env('ADMIN_PASSWORD', 'Admin#Drugmart2026'),
                'role'     => 'admin',
                'title'    => 'Dispensary Lead',
            ]
        );

        // ---- Store defaults ----
        foreach (Setting::DEFAULTS as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget('settings.all');

        // ---- Sample catalog (galing sa Figma design) ----
        if (Product::count() === 0) {
            $this->seedProducts();

            if (app()->environment('local')) {
                $this->seedDemoSales($admin);
            }
        }
    }

    private function seedProducts(): void
    {
        $rows = [
            // name, sku, category, subtitle, unit, price, stock, expires in (days), description
            ['Paracetamol Tablets 500mg', 'PCM-500', 'both', 'Oral Pain Relief & Antipyretic', 'tablet', 8.50, 48, 780, 'Effective relief for mild to moderate pain including headache, migraine, toothache, and reducing fever. Keep in a dry, cool cabinet away from direct sunlight.'],
            ['Amoxicillin Capsules 500mg', 'AMX-500', 'rx', 'Antibiotic', 'capsule', 12.00, 5, 400, 'Prescription antibiotic. Dispense only with a valid prescription.'],
            ['Vitamin C Syrup 120ml', 'VTC-120', 'otc', 'Vitamin', 'bottle', 150.00, 120, 520, 'Daily vitamin C supplement syrup. Shake well before use.'],
            ['Cough Syrup (Dextromethorphan) 60ml', 'CGH-060', 'otc', 'Respiratory', 'bottle', 95.50, 14, 14, 'Cough suppressant for dry, non-productive cough.'],
            ['Ibuprofen Tablets 400mg', 'IBU-400', 'otc', 'Anti-inflammatory', 'tablet', 7.25, 85, 600, 'Relieves pain and inflammation. Take with food.'],
            ['Eye Drops 15ml', 'EYE-015', 'otc', 'Lubricant', 'bottle', 135.00, 3, 300, 'Lubricating eye drops for dryness and irritation.'],
            ['Antiseptic Lotion 250ml', 'ANT-250', 'otc', 'Antiseptic', 'bottle', 65.00, 62, 700, 'Skin antiseptic for minor cuts and scrapes.'],
            ['Bandages (Flexible Fabric)', 'BND-100', 'otc', 'First Aid', 'box', 45.00, 210, 900, 'Flexible fabric adhesive bandages, assorted sizes.'],
        ];

        foreach ($rows as [$name, $sku, $cat, $sub, $unit, $price, $stock, $days, $desc]) {
            Product::create([
                'sku' => $sku, 'name' => $name, 'category' => $cat, 'subtitle' => $sub, 'unit' => $unit,
                'price' => $price, 'stock' => $stock, 'expiration_date' => now()->addDays($days),
                'description' => $desc,
            ]);
        }
    }

    /** Ilang halimbawang benta para hindi blangko ang Dashboard at Reports (local lang). */
    private function seedDemoSales(User $cashier): void
    {
        $products = Product::all()->keyBy('sku');
        $n = 0;

        foreach ([[0, 'PCM-500', 2, 'VTC-120', 1], [0, 'IBU-400', 4, null, 0], [1, 'VTC-120', 2, 'BND-100', 1], [2, 'PCM-500', 10, 'ANT-250', 1], [3, 'BND-100', 3, null, 0]] as [$daysAgo, $a, $qa, $b, $qb]) {
            $lines = [[$products[$a], $qa]];
            if ($b) $lines[] = [$products[$b], $qb];

            $subtotal = collect($lines)->sum(fn ($l) => $l[0]->price * $l[1]);
            $cash = ceil($subtotal / 100) * 100;

            $sale = Sale::create([
                'invoice_no' => sprintf('INV-%d-%04d', now()->year, ++$n),
                'user_id' => $cashier->id, 'subtotal' => $subtotal, 'senior_pwd' => false, 'discount' => 0,
                'total' => $subtotal, 'cash_received' => $cash, 'change_due' => $cash - $subtotal, 'status' => 'completed',
                'created_at' => now()->subDays($daysAgo)->subMinutes($n * 37), 'updated_at' => now(),
            ]);

            foreach ($lines as [$p, $q]) {
                SaleItem::create([
                    'sale_id' => $sale->id, 'product_id' => $p->id, 'product_name' => $p->name,
                    'unit_price' => $p->price, 'quantity' => $q, 'line_total' => $p->price * $q,
                ]);
            }
        }
    }
}
