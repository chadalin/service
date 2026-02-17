<?php
// app/Models/PriceImportRule.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceImportRule extends Model
{
    protected $table = 'price_import_rules';

    protected $fillable = [
        'name',
        'email_account_id',
        'brand_id',
        'email_subject_pattern',
        'email_sender_pattern',
        'filename_patterns',
        'update_existing',
        'match_symptoms',
        'column_mapping',
        'priority',
        'is_active',
        'last_processed_at'
    ];

    protected $casts = [
        'filename_patterns' => 'array',
        'column_mapping' => 'array',
        'update_existing' => 'boolean',
        'match_symptoms' => 'boolean',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'last_processed_at' => 'datetime'
    ];

    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PriceImportLog::class, 'price_import_rule_id');
    }

    /**
     * Проверяет, подходит ли письмо под правило
     */
    public function matchesEmail(string $subject, string $from): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->email_subject_pattern && !preg_match($this->email_subject_pattern, $subject)) {
            return false;
        }

        if ($this->email_sender_pattern && !preg_match($this->email_sender_pattern, $from)) {
            return false;
        }

        return true;
    }

    /**
     * Проверяет, подходит ли файл под правило
     */
    public function matchesFilename(string $filename): bool
    {
        if (empty($this->filename_patterns)) {
            return true;
        }

        foreach ($this->filename_patterns as $pattern) {
            if (preg_match($pattern, $filename)) {
                return true;
            }
        }

        return false;
    }
}