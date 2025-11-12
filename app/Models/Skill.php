<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
    ];

    // Relationships
    public function employees()
    {
        return $this->belongsToMany(EmployeeProfile::class, 'employee_skill')
                    ->withPivot('level')
                    ->withTimestamps();
    }

    public function jobs()
    {
        return $this->belongsToMany(Job::class, 'job_skill')
                    ->withPivot('is_required')
                    ->withTimestamps();
    }

    // Scope to search skills
    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'like', "%{$term}%");
    }

    // Scope to filter by category
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}