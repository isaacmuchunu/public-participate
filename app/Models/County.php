<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class County extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'population',
        'description',
    ];

    public function constituencies(): HasMany
    {
        return $this->hasMany(Constituency::class);
    }

    /**
     * Get users from this county
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get submissions from this county
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'submitter_county', 'name');
    }
}
