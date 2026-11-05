<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Training extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(TrainingAssignment::class);
    }
}
