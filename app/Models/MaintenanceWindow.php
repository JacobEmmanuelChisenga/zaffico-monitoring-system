<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceWindow extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id', 'title', 'description', 'start_time', 'end_time',
        'disable_alerts', 'status', 'created_by'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function application()
    {
        return $this->belongsTo(Applications::class, 'application_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
