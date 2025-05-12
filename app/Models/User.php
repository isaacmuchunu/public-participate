<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'county_id',
        'constituency_id',
        'ward_id',
        'national_id',
        'is_verified',
        'legislative_house',
        'invited_by',
        'invited_at',
        'invitation_expires_at',
        'invitation_token',
        'invitation_used_at',
        'suspended_at',
        'last_active_at',
        'failed_login_attempts',
        'locked_until',
        'otp_code',
        'otp_expires_at',
        'otp_verified_at',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_verified' => 'boolean',
            'role' => UserRole::class,
            'invited_at' => 'datetime',
            'invitation_expires_at' => 'datetime',
            'invitation_used_at' => 'datetime',
            'suspended_at' => 'datetime',
            'last_active_at' => 'datetime',
            'locked_until' => 'datetime',
            'otp_expires_at' => 'datetime',
            'otp_verified_at' => 'datetime',
        ];
    }

    /**
     * Check if user is a clerk or admin
     */
    public function isClerk(): bool
    {
        if ($this->role instanceof UserRole) {
            return $this->role->isClerkish();
        }

        return in_array($this->role, ['clerk', 'admin'], true);
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        if ($this->role instanceof UserRole) {
            return $this->role === UserRole::Admin;
        }

        return $this->role === 'admin';
    }

    public function isCitizen(): bool
    {
        if ($this->role instanceof UserRole) {
            return $this->role === UserRole::Citizen;
        }

        return $this->role === 'citizen';
    }

    public function isLegislator(): bool
    {
        if ($this->role instanceof UserRole) {
            return $this->role->isLegislator();
        }

        return in_array($this->role, ['mp', 'senator'], true);
    }

    /**
     * Get submissions made by this user
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    public function constituency(): BelongsTo
    {
        return $this->belongsTo(Constituency::class);
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    /**
     * Get bills created by this user (for clerks/admins)
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class, 'created_by');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(self::class, 'invited_by');
    }

    public function highlights(): HasMany
    {
        return $this->hasMany(LegislatorHighlight::class);
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function routeNotificationForTwilio(): ?string
    {
        return $this->phone;
    }
}
