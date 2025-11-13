<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = ['business_id', 'template', 'metadata', 'is_active'];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function blocks()
    {
        return $this->hasMany(PageBlock::class)->orderBy('order');
    }
}