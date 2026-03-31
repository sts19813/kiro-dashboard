<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourismUserLocationHistory extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'tourism_user_id',
        'lat',
        'lng',
        'accuracy_meters',
        'budget',
        'search_query',
        'context',
        'recorded_at',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'accuracy_meters' => 'float',
        'budget' => 'decimal:2',
        'context' => 'array',
        'recorded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(TourismUser::class, 'tourism_user_id');
    }
}
