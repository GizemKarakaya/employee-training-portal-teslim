<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingAssignment extends Model
{
    protected $fillable = [
        'training_id',
        'personnel_id',
        'status',
        'assigned_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }
}
