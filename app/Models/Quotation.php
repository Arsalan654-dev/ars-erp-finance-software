<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'quotation_number', 'client_id', 'duplicate_of_id', 'currency', 'exchange_rate', 'subtotal', 'discount',
        'tax', 'total', 'status', 'valid_until', 'notes', 'terms', 'created_by',
    ];

    protected $casts = [
        'valid_until' => 'date',
        'exchange_rate' => 'decimal:6',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function duplicateOf()
    {
        return $this->belongsTo(Quotation::class, 'duplicate_of_id');
    }

    public function isDuplicate(): bool
    {
        return (bool) $this->duplicate_of_id;
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(QuotationStatusHistory::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}