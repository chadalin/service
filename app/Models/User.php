<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'pin_code',
        'pin_expires_at',
        'company_name',
        'role',
        'status'
    ];

    protected $hidden = [
        'password',
        'pin_code',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'pin_expires_at' => 'datetime',
    ];

    public function documents()
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    public function searchQueries()
    {
        return $this->hasMany(SearchQuery::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isPinValid()
    {
        return $this->pin_expires_at && $this->pin_expires_at->isFuture();
    }
}