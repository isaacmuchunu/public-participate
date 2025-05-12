<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BillClause extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_id',
        'clause_number',
        'clause_type',
        'parent_clause_id',
        'title',
        'content',
        'metadata',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * Get the bill this clause belongs to
     */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    /**
     * Get the parent clause
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(BillClause::class, 'parent_clause_id');
    }

    /**
     * Get child clauses
     */
    public function children(): HasMany
    {
        return $this->hasMany(BillClause::class, 'parent_clause_id')
            ->orderBy('display_order');
    }

    /**
     * Get submissions for this clause
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'clause_id');
    }

    /**
     * Get analytics for this clause
     */
    public function analytics(): HasOne
    {
        return $this->hasOne(ClauseAnalytics::class, 'clause_id');
    }

    /**
     * Scope for top-level clauses (sections)
     */
    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_clause_id');
    }

    /**
     * Scope for ordered clauses
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order');
    }

    /**
     * Get full clause number including parent hierarchy
     */
    public function getFullClauseNumber(): string
    {
        if ($this->parent) {
            return $this->parent->getFullClauseNumber().'.'.$this->clause_number;
        }

        return $this->clause_number;
    }

    /**
     * Get hierarchical clause path
     */
    public function getClausePath(): array
    {
        $path = [$this];

        $current = $this;
        while ($current->parent) {
            $current = $current->parent;
            array_unshift($path, $current);
        }

        return $path;
    }

    /**
     * Check if clause has children
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }
}
