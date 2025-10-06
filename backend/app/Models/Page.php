<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'content',
        'theme_config',
        'is_published'
    ];

    protected $casts = [
        'content' => 'array',
        'theme_config' => 'array',
        'is_published' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}