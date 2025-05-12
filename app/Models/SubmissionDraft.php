<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bill_id',
        'submission_type',
        'language',
        'content',
        'contact_information',
        'attachments',
        'submitted_at',
    ];

    protected $casts = [
        'contact_information' => 'array',
        'attachments' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }
}
