<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'receive_alerts',
        'phone_number'
    ];

    // Relationships

    // Applications created by user
    public function applications()
    {
        return $this->hasMany(Applications::class, 'created_by');
    }

    // Downtime incidents resolved by this user
    public function resolvedIncidents()
    {
        return $this->hasMany(DowntimeIncident::class, 'resolved_by');
    }

    // Maintenance windows created by this user
    public function maintenanceWindows()
    {
        return $this->hasMany(MaintenanceWindow::class, 'created_by');
    }

    // Activity logs
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }
}
