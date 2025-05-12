<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Bill extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'bill_number',
        'description',
        'type',
        'house',
        'status',
        'sponsor',
        'committee',
        'gazette_date',
        'participation_start_date',
        'participation_end_date',
        'pdf_path',
        'tags',
        'views_count',
        'submissions_count',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'gazette_date' => 'date',
            'participation_start_date' => 'date',
            'participation_end_date' => 'date',
            'tags' => 'array',
            'views_count' => 'integer',
            'submissions_count' => 'integer',
        ];
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bill) {
            if (empty($bill->bill_number)) {
                $bill->bill_number = 'BILL-'.date('Y').'-'.Str::upper(Str::random(6));
            }
        });
    }

    /**
     * Get the user who created this bill
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get submissions for this bill
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function highlights(): HasMany
    {
        return $this->hasMany(LegislatorHighlight::class);
    }

    /**
     * Get the bill summary
     */
    public function summary(): HasOne
    {
        return $this->hasOne(BillSummary::class);
    }

    /**
     * Get all clauses for this bill
     */
    public function clauses(): HasMany
    {
        return $this->hasMany(BillClause::class);
    }

    /**
     * Get top-level clauses (sections)
     */
    public function topLevelClauses(): HasMany
    {
        return $this->hasMany(BillClause::class)
            ->whereNull('parent_clause_id')
            ->orderBy('display_order');
    }

    /**
     * Check if bill is open for participation
     */
    public function isOpenForParticipation(): bool
    {
        return $this->status === 'open_for_participation' &&
               $this->participation_start_date <= now() &&
               $this->participation_end_date >= now();
    }

    /**
     * Get days remaining for participation
     */
    public function daysRemaining(): int
    {
        if (! $this->isOpenForParticipation()) {
            return 0;
        }

        return now()->diffInDays($this->participation_end_date, false);
    }

    /**
     * Scope for bills open for participation
     */
    public function scopeOpenForParticipation(Builder $query): Builder
    {
        return $query->where('status', 'open_for_participation')
            ->where('participation_start_date', '<=', now())
            ->where('participation_end_date', '>=', now());
    }

    /**
     * Scope for bills by tag
     */
    public function scopeByTag(Builder $query, string $tag): Builder
    {
        return $query->whereJsonContains('tags', $tag);
    }
}
