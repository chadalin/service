<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'url',
        'description',
        'login',
        'password',
        'auth_type',
        'additional_data'
    ];

    protected $casts = [
        'additional_data' => 'array'
    ];

    public function getDomainAttribute()
    {
        $parsed = parse_url($this->url);
        return $parsed['host'] ?? '';
    }

    public function getFormattedUrlAttribute()
    {
        return route('links.redirect', $this->id);
    }
}