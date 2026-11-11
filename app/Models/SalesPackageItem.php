<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesPackageItem extends Model
{
    protected $fillable = ['sales_package_id', 'description', 'quantity', 'unit_price', 'sort_order'];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(SalesPackage::class, 'sales_package_id');
    }
}
