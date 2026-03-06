<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiCall extends Model
{
    public $timestamps = false;

    protected $table = 'api_calls';

    protected $fillable = [
        'api_key_id',
        'endpoint',
        'method',
        'national_id_requested',
        'response_status',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'api_key_id' => 'integer',
        'response_status' => 'integer',
    ];

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }
}
