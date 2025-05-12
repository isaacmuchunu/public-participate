<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClauseAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'clause_id',
        'submissions_count',
        'support_count',
        'oppose_count',
        'neutral_count',
        'sentiment_scores',
        'top_keywords',
        'last_analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'sentiment_scores' => 'array',
            'top_keywords' => 'array',
            'last_analyzed_at' => 'datetime',
        ];
    }

    /**
     * Get the clause these analytics belong to
     */
    public function clause(): BelongsTo
    {
        return $this->belongsTo(BillClause::class, 'clause_id');
    }

    /**
     * Calculate overall sentiment percentage
     */
    public function getSupportPercentage(): float
    {
        $total = $this->submissions_count;

        if ($total === 0) {
            return 0;
        }

        return round(($this->support_count / $total) * 100, 2);
    }

    /**
     * Calculate opposition percentage
     */
    public function getOpposePercentage(): float
    {
        $total = $this->submissions_count;

        if ($total === 0) {
            return 0;
        }

        return round(($this->oppose_count / $total) * 100, 2);
    }

    /**
     * Calculate neutral percentage
     */
    public function getNeutralPercentage(): float
    {
        $total = $this->submissions_count;

        if ($total === 0) {
            return 0;
        }

        return round(($this->neutral_count / $total) * 100, 2);
    }

    /**
     * Get dominant sentiment
     */
    public function getDominantSentiment(): string
    {
        $max = max($this->support_count, $this->oppose_count, $this->neutral_count);

        if ($max === $this->support_count) {
            return 'support';
        }

        if ($max === $this->oppose_count) {
            return 'oppose';
        }

        return 'neutral';
    }
}
