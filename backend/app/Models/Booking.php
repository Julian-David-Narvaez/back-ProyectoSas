<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Booking extends Model
{
    protected $fillable = [
        'business_id',
        'service_id',
        'user_id',
        'customer_name',
        'customer_email',
        'start_at',
        'end_at',
        'status'
    ];

    protected $casts = [
        'start_at' => 'datetime:Y-m-d H:i:s',
        'end_at' => 'datetime:Y-m-d H:i:s',
    ];

    // Asegurar que las fechas se manejen en la zona horaria correcta
    protected $dates = ['start_at', 'end_at'];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessor para asegurar zona horaria correcta
    public function getStartAtAttribute($value)
    {
        return Carbon::parse($value)->timezone('America/Bogota');
    }

    public function getEndAtAttribute($value)
    {
        return Carbon::parse($value)->timezone('America/Bogota');
    }
}