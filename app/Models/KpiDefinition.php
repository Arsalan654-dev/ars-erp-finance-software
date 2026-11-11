<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiDefinition extends Model
{
    protected $fillable = ['slug', 'name', 'description', 'unit', 'calculation_method', 'frequency', 'direction', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function targets()
    {
        return $this->hasMany(KpiTarget::class);
    }

    public function results()
    {
        return $this->hasMany(EmployeeKpiResult::class);
    }
}