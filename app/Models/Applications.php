<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Applications extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'ip_address', 'port', 'protocol', 'endpoint', 'description',
        'check_interval', 'timeout', 'status', 'response_time', 'is_active', 'created_by'
    ];

    protected $casts = [
        'last_checked_at' => 'datetime',
    ];

    // Relationships

    // Creator
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Monitoring checks
    public function checks()
    {
        return $this->hasMany(MonitoringCheck::class, 'application_id');
    }

    // Downtime incidents
    public function incidents()
    {
        return $this->hasMany(DowntimeIncident::class, 'application_id');
    }

    // Alert rules
    public function alertRules()
    {
        return $this->hasMany(AlertRule::class, 'application_id');
    }

    // Notifications
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'application_id');
    }

    // Uptime reports
    public function uptimeReports()
    {
        return $this->hasMany(UptimeReport::class, 'application_id');
    }

    // Maintenance windows
    public function maintenanceWindows()
    {
        return $this->hasMany(MaintenanceWindow::class, 'application_id');
    }

    // Tags (many-to-many)
    public function tags()
    {
        return $this->belongsToMany(Tags::class, 'application_tag', 'application_id', 'tag_id');
    }
}
