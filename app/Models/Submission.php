<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'tracking_id',
        'bill_id',
        'user_id',
        'submitter_name',
        'submitter_phone',
        'submitter_email',
        'submitter_county',
        'submission_type',
        'content',
        'channel',
        'language',
        'status',
        'review_notes',
        'metadata',
        'reviewed_at',
        'reviewed_by',
        'submitted_at',
        'flagged_by',
        'flagged_at',
        'flag_reason',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'flagged_at' => 'datetime',
        ];
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($submission) {
            if (empty($submission->tracking_id)) {
                $submission->tracking_id = Str::upper(Str::random(12));
            }
        });
    }

    /**
     * Get the bill this submission belongs to
     */
    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    /**
     * Get the user who made this submission
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who reviewed this submission
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function engagements(): HasMany
    {
        return $this->hasMany(CitizenEngagement::class);
    }

    /**
     * Get the county this submission is from
     */
    public function county()
    {
        return $this->belongsTo(County::class, 'submitter_county', 'name');
    }

    /**
     * Get the clause this submission is for
     */
    public function clause()
    {
        return $this->belongsTo(BillClause::class, 'clause_id');
    }

    /**
     * Scope for submissions by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('submission_type', $type);
    }

    /**
     * Scope for submissions by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for submissions by county
     */
    public function scopeByCounty($query, $county)
    {
        return $query->where('submitter_county', $county);
    }
}
