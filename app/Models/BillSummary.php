<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_id',
        'simplified_summary_en',
        'simplified_summary_sw',
        'key_clauses',
        'audio_path_en',
        'audio_path_sw',
        'generation_method',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'key_clauses' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    /**
     * Get the bill this summary belongs to
     */
    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    /**
     * Get the summary in the specified language
     */
    public function getSummary($language = 'en'): ?string
    {
        return $language === 'sw' ? $this->simplified_summary_sw : $this->simplified_summary_en;
    }

    /**
     * Get the audio path for the specified language
     */
    public function getAudioPath($language = 'en'): ?string
    {
        return $language === 'sw' ? $this->audio_path_sw : $this->audio_path_en;
    }

    /**
     * Check if summary has audio in the specified language
     */
    public function hasAudio($language = 'en'): bool
    {
        $audioPath = $this->getAudioPath($language);

        return ! empty($audioPath) && file_exists(storage_path('app/public/'.$audioPath));
    }
}
