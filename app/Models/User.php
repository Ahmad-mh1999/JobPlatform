<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'profile_image',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // JWT Methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
            'email' => $this->email,
        ];
    }

    // Relationships
    public function employeeProfile()
    {
        return $this->hasOne(EmployeeProfile::class);
    }

    public function company()
    {
        return $this->hasOne(Company::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    // Accessor to get profile based on role
    public function getProfileAttribute()
    {
        if ($this->role === 'employee') {
            return $this->employeeProfile;
        } elseif ($this->role === 'company') {
            return $this->company;
        }
        return null;
    }

    // Check if user is employee
    public function isEmployee()
    {
        return $this->role === 'employee';
    }

    // Check if user is company
    public function isCompany()
    {
        return $this->role === 'company';
    }

    // Check if user is admin
    public function isAdmin()
    {
        return $this->role === 'admin';
    }
}