<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name', 'slug', 'head_id', 'is_active'];

    public function head() { return $this->belongsTo(User::class, 'head_id'); }
    public function members() { return $this->belongsToMany(User::class, 'department_members')->withPivot('role_in_department'); }
    public function projects() { return $this->hasMany(Project::class, 'department', 'slug'); }
}
