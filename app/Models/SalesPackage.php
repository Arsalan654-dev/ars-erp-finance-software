<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Reusable sales package template (GBP/USD priced line item)
 * that can be dropped into a quotation or invoice.
 */
class SalesPackage extends Model
{
    protected $fillable = ['name', 'description', 'unit_price', 'currency', 'is_active'];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Priced bundle lines, in display order. Packages without lines
     * behave as a single line item (legacy behaviour).
     */
    public function items(): HasMany
    {
        return $this->hasMany(SalesPackageItem::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Lines to drop into a quotation/invoice: the bundle items when
     * present, otherwise the legacy single package line.
     */
    public function quotationLines(): array
    {
        if ($this->items->isNotEmpty()) {
            return $this->items->map(fn (SalesPackageItem $item) => [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
            ])->all();
        }

        return [[
            'description' => $this->name,
            'quantity' => 1,
            'unit_price' => (float) $this->unit_price,
        ]];
    }
}
