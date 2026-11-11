<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeKpiResult extends Model
{
    protected $fillable = ['kpi_definition_id', 'user_id', 'actual_value', 'target_value', 'score', 'period', 'notes'];

    public function kpiDefinition()
    {
        return $this->belongsTo(KpiDefinition::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}