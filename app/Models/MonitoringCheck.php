<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitoringCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id', 'status', 'response_time', 'status_code', 'error_message', 'response_body', 'checked_at'
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    public function application()
    {
        return $this->belongsTo(Applications::class, 'application_id');
    }
}
