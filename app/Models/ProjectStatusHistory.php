<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectStatusHistory extends Model
{
    protected $table = 'project_status_history';
    protected $fillable = ['project_id', 'from_status', 'to_status', 'changed_by', 'reason'];
    public $timestamps = false;

    public function project() { return $this->belongsTo(Project::class); }
    public function changedBy() { return $this->belongsTo(User::class, 'changed_by'); }
}
