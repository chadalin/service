<?php
// app/Models/PriceImportLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceImportLog extends Model
{
    protected $table = 'price_import_logs';

    protected $fillable = [
        'email_account_id',
        'price_import_rule_id',
        'brand_id',
        'email_subject',
        'email_from',
        'filename',
        'status',
        'items_processed',
        'items_created',
        'items_updated',
        'items_skipped',
        'details',
        'error_message'
    ];

    protected $casts = [
        'details' => 'array',
        'items_processed' => 'integer',
        'items_created' => 'integer',
        'items_updated' => 'integer',
        'items_skipped' => 'integer'
    ];

    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(PriceImportRule::class, 'price_import_rule_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }
}