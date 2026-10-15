<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DowntimeIncident extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id', 'alert_rule_id', 'started_at', 'resolved_at', 'duration', 'severity',
        'error_message', 'resolution_notes', 'resolved_by', 'notification_sent'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function application()
    {
        return $this->belongsTo(Applications::class, 'application_id');
    }

    public function alertRule()
    {
        return $this->belongsTo(AlertRule::class);
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'incident_id');
    }
}
