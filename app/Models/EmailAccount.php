<?php
// app/Models/EmailAccount.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailAccount extends Model
{
    protected $fillable = [
        'name',
        'email',
        'imap_host',
        'imap_port',
        'imap_encryption',
        'username',
        'password',
        'mailbox',
        'is_active',
        'check_interval',
        'last_checked_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_checked_at' => 'datetime',
        'imap_port' => 'integer',
        'check_interval' => 'integer'
    ];

    protected $hidden = ['password'];

    public function rules(): HasMany
    {
        return $this->hasMany(PriceImportRule::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PriceImportLog::class);
    }
}