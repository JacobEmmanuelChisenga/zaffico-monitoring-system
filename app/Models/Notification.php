<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id', 'incident_id', 'type', 'recipient', 'subject', 'message', 'status', 'sent_at', 'error_message'
    ];

    public function application()
    {
        return $this->belongsTo(Applications::class, 'application_id');
    }

    public function incident()
    {
        return $this->belongsTo(DowntimeIncident::class, 'incident_id');
    }
}
