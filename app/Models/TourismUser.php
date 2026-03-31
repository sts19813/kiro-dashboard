<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TourismUser extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'phone',
        'name',
        'email',
        'preferred_language',
        'currency_code',
        'budget_min',
        'budget_max',
        'preferences',
        'is_active',
    ];

    protected $casts = [
        'preferences' => 'array',
        'is_active' => 'boolean',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
    ];

    public function locations(): HasMany
    {
        return $this->hasMany(TourismUserLocationHistory::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(TourismUserChatMessage::class);
    }
}
