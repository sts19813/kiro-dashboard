<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationPoint extends Model
{
    protected $fillable = [
        'name',
        'category',
        'city',
        'address',
        'phone',
        'email',
        'website',
        'description',
        'lat',
        'lng',
        'tags',
        'metadata',
        'source',
        'is_active',
    ];

    protected $casts = [
        'tags' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'lat' => 'float',
        'lng' => 'float',
    ];
}
