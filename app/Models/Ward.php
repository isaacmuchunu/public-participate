<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ward extends Model
{
    use HasFactory;

    protected $fillable = [
        'constituency_id',
        'name',
        'code',
    ];

    public function constituency(): BelongsTo
    {
        return $this->belongsTo(Constituency::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
