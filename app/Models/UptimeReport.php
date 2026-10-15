<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UptimeReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id', 'report_date', 'period_type', 'uptime_percentage',
        'total_checks', 'successful_checks', 'failed_checks',
        'total_downtime_minutes', 'incident_count', 'avg_response_time',
        'min_response_time', 'max_response_time'
    ];

    public function application()
    {
        return $this->belongsTo(Applications::class, 'application_id');
    }
}
