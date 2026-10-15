<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlertRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id', 'trigger_type', 'threshold_value', 'consecutive_failures',
        'notification_channels', 'recipients', 'is_active', 'condition_type',
        'condition_value', 'severity', 'notification_email', 'notification_slack_webhook'
    ];

    protected $casts = [
        'notification_channels' => 'array',
        'recipients' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function application()
    {
        return $this->belongsTo(Applications::class, 'application_id');
    }

    public function monitoringCheck()
    {
        return $this->belongsTo(MonitoringCheck::class);
    }
}
