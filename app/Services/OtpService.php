<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    private const OTP_LENGTH = 6;

    private const OTP_EXPIRY_MINUTES = 10;

    private const MAX_ATTEMPTS = 3;

    private const LOCKOUT_MINUTES = 15;

    /**
     * Generate and store OTP for user
     */
    public function generateOtp(User $user, string $purpose = 'registration'): string
    {
        // Check if user is locked out
        if ($this->isLockedOut($user, $purpose)) {
            throw new \Exception('Too many failed attempts. Please try again later.');
        }

        // Generate numeric OTP
        $otp = $this->generateNumericOtp();

        // Hash the OTP before storing
        $hashedOtp = Hash::make($otp);

        // Store OTP in user record
        $user->update([
            'otp_code' => $hashedOtp,
            'otp_expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
            'otp_verified_at' => null,
        ]);

        // Reset attempt counter
        $this->resetAttempts($user, $purpose);

        // Store purpose in cache for validation
        Cache::put(
            $this->getPurposeCacheKey($user, $purpose),
            $purpose,
            now()->addMinutes(self::OTP_EXPIRY_MINUTES)
        );

        return $otp; // Return plain OTP for sending via SMS/email
    }

    /**
     * Verify OTP for user
     */
    public function verifyOtp(User $user, string $otp, string $purpose = 'registration'): bool
    {
        // Check if user is locked out
        if ($this->isLockedOut($user, $purpose)) {
            throw new \Exception('Too many failed attempts. Please try again later.');
        }

        // Check if OTP exists and hasn't expired
        if (! $user->otp_code || ! $user->otp_expires_at) {
            $this->incrementAttempts($user, $purpose);

            return false;
        }

        // Check expiration
        if ($user->otp_expires_at < now()) {
            $this->incrementAttempts($user, $purpose);

            return false;
        }

        // Verify OTP matches
        if (! Hash::check($otp, $user->otp_code)) {
            $this->incrementAttempts($user, $purpose);

            return false;
        }

        // Verify purpose matches
        $storedPurpose = Cache::get($this->getPurposeCacheKey($user, $purpose));
        if ($storedPurpose !== $purpose) {
            $this->incrementAttempts($user, $purpose);

            return false;
        }

        // Mark OTP as verified and clear it
        $user->update([
            'otp_verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        // Clear purpose cache and attempt counter
        Cache::forget($this->getPurposeCacheKey($user, $purpose));
        $this->resetAttempts($user, $purpose);

        return true;
    }

    /**
     * Check if user is currently locked out
     */
    public function isLockedOut(User $user, string $purpose = 'registration'): bool
    {
        $lockoutKey = $this->getLockoutCacheKey($user, $purpose);

        return Cache::has($lockoutKey);
    }

    /**
     * Get remaining lockout time in seconds
     */
    public function getLockoutTime(User $user, string $purpose = 'registration'): int
    {
        $lockoutKey = $this->getLockoutCacheKey($user, $purpose);
        $lockoutExpiry = Cache::get($lockoutKey);

        if (! $lockoutExpiry) {
            return 0;
        }

        return max(0, $lockoutExpiry - now()->timestamp);
    }

    /**
     * Generate a numeric OTP
     */
    private function generateNumericOtp(): string
    {
        return str_pad(
            (string) random_int(0, 999999),
            self::OTP_LENGTH,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * Increment failed verification attempts
     */
    private function incrementAttempts(User $user, string $purpose): void
    {
        $attemptsKey = $this->getAttemptsCacheKey($user, $purpose);
        $attempts = Cache::get($attemptsKey, 0);
        $attempts++;

        if ($attempts >= self::MAX_ATTEMPTS) {
            // Lock out the user
            $this->lockoutUser($user, $purpose);
        } else {
            Cache::put(
                $attemptsKey,
                $attempts,
                now()->addMinutes(self::OTP_EXPIRY_MINUTES)
            );
        }
    }

    /**
     * Reset failed verification attempts
     */
    private function resetAttempts(User $user, string $purpose): void
    {
        Cache::forget($this->getAttemptsCacheKey($user, $purpose));
    }

    /**
     * Lock out user after too many failed attempts
     */
    private function lockoutUser(User $user, string $purpose): void
    {
        $lockoutKey = $this->getLockoutCacheKey($user, $purpose);
        $lockoutUntil = now()->addMinutes(self::LOCKOUT_MINUTES)->timestamp;

        Cache::put($lockoutKey, $lockoutUntil, now()->addMinutes(self::LOCKOUT_MINUTES));

        // Clear attempts counter
        $this->resetAttempts($user, $purpose);
    }

    /**
     * Get cache key for OTP purpose
     */
    private function getPurposeCacheKey(User $user, string $purpose): string
    {
        return "otp_purpose_{$user->id}_{$purpose}";
    }

    /**
     * Get cache key for failed attempts
     */
    private function getAttemptsCacheKey(User $user, string $purpose): string
    {
        return "otp_attempts_{$user->id}_{$purpose}";
    }

    /**
     * Get cache key for lockout status
     */
    private function getLockoutCacheKey(User $user, string $purpose): string
    {
        return "otp_lockout_{$user->id}_{$purpose}";
    }

    /**
     * Resend OTP (same as generate but with rate limiting)
     */
    public function resendOtp(User $user, string $purpose = 'registration'): string
    {
        // Check rate limiting for resend (max 3 resends per 30 minutes)
        $resendKey = "otp_resend_{$user->id}_{$purpose}";
        $resendCount = Cache::get($resendKey, 0);

        if ($resendCount >= 3) {
            throw new \Exception('Too many resend attempts. Please try again later.');
        }

        $otp = $this->generateOtp($user, $purpose);

        // Increment resend counter
        Cache::put($resendKey, $resendCount + 1, now()->addMinutes(30));

        return $otp;
    }
}
