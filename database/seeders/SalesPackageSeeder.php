<?php

namespace Database\Seeders;

use App\Models\SalesPackage;
use Illuminate\Database\Seeder;

/**
 * USA LLC / UK formation package templates (multi-line bundles).
 * Idempotent: safe to re-run — packages are matched by name and their
 * lines are rebuilt every run.
 */
class SalesPackageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->packages() as $package) {
            $model = SalesPackage::updateOrCreate(
                ['name' => $package['name']],
                [
                    'description' => $package['description'],
                    'currency' => $package['currency'],
                    'is_active' => true,
                    'unit_price' => collect($package['lines'])->sum(fn ($line) => $line[1] * $line[2]),
                ]
            );

            $model->items()->delete();
            foreach ($package['lines'] as $index => [$description, $quantity, $unitPrice]) {
                $model->items()->create([
                    'description' => $description,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'sort_order' => $index,
                ]);
            }
        }
    }

    /**
     * @return array<int, array{name: string, description: string, currency: string, lines: array<int, array{0: string, 1: int, 2: float}>}>
     */
    private function packages(): array
    {
        $wise = 'Wise/Payoneer business bank account signup (subject to approval)';
        $stripe = 'Stripe/PayPal business account signup (subject to approval, any 01)';
        $wiseNote = 'Excl. US phone, Wise one-time fee $31 (pay with director-name card). Wise offers no USD currency to non-residents.';

        $standard = fn (string $state, float $regFee, float $total): array => [
            'name' => "USA {$state} LLC Package",
            'description' => "Standard {$state} LLC formation bundle. Sub total \${$total}. {$wiseNote}",
            'currency' => 'USD',
            'lines' => [
                ["USA LLC Registration ({$state})", 1, $regFee],
                ['Registered Agent & Processing fee', 1, 50],
                ['Unique Commercial address (1 Year)', 1, 100],
                ['Express EIN', 1, 50],
                [$wise, 1, 0],
                [$stripe, 1, 0],
                ['VPS (1 Month)', 1, 10],
                ['Service Fee', 1, 100],
            ],
        ];

        $amazon = fn (string $state, float $regFee, float $resellerFee, float $total): array => [
            'name' => "USA {$state} LLC with Amazon Package",
            'description' => "{$state} LLC + Amazon seller bundle. Sub total \${$total}. {$wiseNote} Excl. Amazon fee.",
            'currency' => 'USD',
            'lines' => [
                ["USA LLC Registration ({$state})", 1, $regFee],
                ['Registered Agent & Processing fee', 1, 50],
                ['Unique Commercial address 1 Year (with notarization)', 1, 180],
                ['Express EIN', 1, 50],
                [$wise, 1, 0],
                [$stripe, 1, 0],
                ['VPS (1 Month)', 1, 10],
                ['Amazon Seller Central Signup (subject to approval)', 1, 0],
                ['Reseller Certificate', 1, $resellerFee],
                ['Service Fee', 1, 150],
            ],
        ];

        return [
            $standard('Texas', 310, 620),
            $standard('Florida', 135, 445),
            $standard('Wyoming', 110, 420),
            $standard('Montana', 45, 355),
            $standard('Colorado', 60, 370),
            $amazon('Texas', 310, 0, 750),
            $amazon('Florida', 135, 0, 575),
            $amazon('Wyoming', 110, 0, 550),
            $amazon('Montana', 45, 0, 485),
            $amazon('Colorado', 60, 50, 550),
            [
                'name' => 'UK Ltd Formation',
                'description' => 'UK Ltd formation bundle. Total 305 GBP, discounted offer 285 GBP. 15-20 business days. Excl. UK phone & Wise one-time fee 50 GBP (Sadapay/Nayapay preferable).',
                'currency' => 'GBP',
                'lines' => [
                    ['UK LTD Formation', 1, 100],
                    ['UK Identity Verification (per director)', 1, 25],
                    ['UK Unique Physical Address (per annum)', 1, 120],
                    ['Service Fee', 1, 50],
                    ['Wise/Payoneer Business Bank Signup (subject to approval)', 1, 0],
                    ['PayPal/Stripe Business Signup (subject to approval, any 01)', 1, 0],
                    ['VPS (1 month)', 1, 10],
                ],
            ],
            [
                'name' => 'UK A to Z Services',
                'description' => 'UK formation + Amazon bundle. Total 335 GBP, discounted offer 300 GBP. 20-25 business days. Excl. UK phone, Amazon fee & Wise one-time fee 50 GBP.',
                'currency' => 'GBP',
                'lines' => [
                    ['UK LTD Formation', 1, 100],
                    ['UK Identity Verification (per director)', 1, 25],
                    ['UK Unique Physical Address (per annum)', 1, 120],
                    ['Service Fee', 1, 80],
                    ['Amazon Seller Central Signup (subject to approval)', 1, 0],
                    ['Wise/Payoneer Business Bank Signup (subject to approval)', 1, 0],
                    ['PayPal/Stripe Business Signup (subject to approval, any 01)', 1, 0],
                    ['VPS (1 month)', 1, 10],
                ],
            ],
            [
                'name' => 'UK Trademark Registration',
                'description' => 'UK trademark filing. 450 GBP, discounted offer 380 GBP. +100 GBP per additional class. Draft in 15-20 business days, documents in 4-6 months.',
                'currency' => 'GBP',
                'lines' => [
                    ['UK Trademark Charges', 1, 450],
                ],
            ],
        ];
    }
}
