<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tags extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'color'];

    public function applications()
    {
        return $this->belongsToMany(Applications::class, 'application_tag');
    }
}
