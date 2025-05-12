<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegislatorHighlight extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bill_id',
        'submission_id',
        'title',
        'clause_reference',
        'excerpt',
        'note',
        'metadata',
        'highlighted_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'highlighted_at' => 'datetime',
        ];
    }

    public function legislator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
