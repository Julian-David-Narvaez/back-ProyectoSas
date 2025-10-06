<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'slug',
        'description',
        'logo_url',
        'address',
        'phone',
        'email',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Auto-generar slug
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($business) {
            if (empty($business->slug)) {
                $business->slug = Str::slug($business->business_name);
                
                // Asegurar que el slug sea único
                $count = static::where('slug', 'like', $business->slug . '%')->count();
                if ($count > 0) {
                    $business->slug = $business->slug . '-' . ($count + 1);
                }
            }
        });
    }

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function page()
    {
        return $this->hasOne(Page::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function workingHours()
    {
        return $this->hasMany(WorkingHour::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}