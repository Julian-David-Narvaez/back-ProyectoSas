<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'business_id',
        'weekday',
        'start_time',
        'end_time'
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}